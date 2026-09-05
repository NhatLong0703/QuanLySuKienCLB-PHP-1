<?php
class RegistrationController extends BaseController {
    private $regRepo;
    private $eventRepo;
    public function __construct() { $this->regRepo = new RegistrationRepository(); $this->eventRepo = new EventRepository(); }

    // POST /api/registration/register
    public function register() {
        if ($_SERVER['REQUEST_METHOD']!=='POST') return $this->json(['message'=>'Method Not Allowed'],405);
        $d = $this->getJsonInput();
        if (empty($d['event_id'])||empty($d['user_id'])) return $this->json(['status'=>'error','message'=>'Thieu thong tin'],400);
        $ev = $this->eventRepo->findById($d['event_id']);
        if (!$ev) return $this->json(['status'=>'error','message'=>'Su kien khong ton tai'],404);
        if ($ev['status'] !== 'open') return $this->json(['status'=>'error','message'=>'Su kien khong con mo dang ky'],400);
        if ($ev['slots_left'] <= 0) return $this->json(['status'=>'error','message'=>'Su kien da het cho'],400);
        $existing = $this->regRepo->findByEventAndUser($d['event_id'], $d['user_id']);
        if ($existing && $existing->getStatus()==='registered') return $this->json(['status'=>'error','message'=>'Ban da dang ky su kien nay roi'],400);
        try {
            $id = $this->regRepo->create($d['event_id'], $d['user_id']);
            $this->logAudit($d['user_id'], 'Register Event', 'registrations', $id, 'User registered for event ID: ' . $d['event_id']);
            return $this->json(['status'=>'success','message'=>'Dang ky thanh cong'],201);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            // Handle trigger error message if it's from MySQL signal
            if (strpos($msg, '1644') !== false) {
                // Extract the custom message from the SQLSTATE[45000] error
                $parts = explode('1644', $msg);
                if (isset($parts[1])) {
                    $msg = trim($parts[1]);
                } else {
                    $msg = 'Đã quá hạn đăng ký hoặc có lỗi xảy ra.';
                }
            } else if (strpos($msg, 'Duplicate entry') !== false) {
                $msg = 'Bạn đã đăng ký sự kiện này rồi.';
            } else {
                $msg = 'Lỗi hệ thống khi đăng ký.';
            }
            return $this->json(['status'=>'error','message'=>$msg], 400);
        }
    }

    // POST /api/registration/cancel
    public function cancel() {
        $user = $this->getCurrentUser();
        $d = $this->getJsonInput();
        if (empty($d['registration_id'])||empty($d['event_id'])) return $this->json(['status'=>'error','message'=>'Thieu thong tin'],400);
        $this->regRepo->cancel($d['registration_id']);
        $this->logAudit($user['id'] ?? 0, 'Cancel Registration', 'registrations', $d['registration_id'], 'Cancelled registration ID: ' . $d['registration_id']);
        return $this->json(['status'=>'success','message'=>'Da huy dang ky']);
    }

    // GET /api/registration/byEvent?event_id=X
    public function byEvent() {
        $data = $this->regRepo->getByEvent($_GET['event_id']??0);
        return $this->json(['status'=>'success','data'=>$data]);
    }

    // GET /api/registration/mine?user_id=X
    public function mine() {
        $data = $this->regRepo->getMyRegistrations($_GET['user_id']??0);
        return $this->json(['status'=>'success','data'=>$data]);
    }

    // POST /api/registration/selfCheckIn
    public function selfCheckIn() {
        if ($_SERVER['REQUEST_METHOD']!=='POST') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        $d = $this->getJsonInput();
        if (empty($d['registration_id'])) return $this->json(['status'=>'error','message'=>'Thieu thong tin'],400);
        $this->regRepo->update($d['registration_id'], ['status' => 'pending_verification']);
        $this->logAudit($user['id'], 'Self Check-in', 'registrations', $d['registration_id'], 'Member requested check-in for registration ID: ' . $d['registration_id']);
        return $this->json(['status'=>'success','message'=>'Check-in submitted! Waiting for verification.']);
    }

    // POST /api/registration/verifyAttendance
    public function verifyAttendance() {
        if ($_SERVER['REQUEST_METHOD']!=='POST') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') return $this->json(['status'=>'error','message'=>'Ban khong co quyen'],403);
        $d = $this->getJsonInput();
        if (empty($d['registration_id'])) return $this->json(['status'=>'error','message'=>'Thieu thong tin'],400);
        
        $reg = $this->regRepo->findById($d['registration_id']);
        if (!$reg) return $this->json(['status'=>'error','message'=>'Dang ky khong ton tai'],404);

        // First insert into attendance (BEFORE updating status, so trigger passes)
        // Use INSERT IGNORE to handle cases where attendance record already exists
        $stmt = $this->regRepo->getDb()->prepare("INSERT IGNORE INTO attendance (registration_id, checked_in_by, checked_in_at) VALUES (?, ?, NOW())");
        $stmt->execute([$d['registration_id'], $user['id']]);
        
        // Then update registration status to attended
        $this->regRepo->update($d['registration_id'], ['status' => 'attended']);

        // Create Targeted Notification for the member
        $regData = $this->regRepo->getDb()->prepare("SELECT r.user_id, r.event_id, e.title FROM registrations r JOIN events e ON e.id = r.event_id WHERE r.id = ?");
        $regData->execute([$d['registration_id']]);
        $regRow = $regData->fetch(PDO::FETCH_ASSOC);
        if ($regRow) {
            $eventTitle = $regRow['title'];
            $notiRepo = new NotificationRepository();
            $notiRepo->create([
                'event_id'   => $regRow['event_id'],
                'user_id'    => $regRow['user_id'],
                'title'      => 'Xac nhan diem danh',
                'content'    => "Tu diem danh cua ban cho su kien '{$eventTitle}' da duoc Quan ly xac nhan thanh cong!",
                'created_by' => $user['id']
            ]);
        }

        $this->logAudit($user['id'], 'Verify Attendance', 'registrations', $d['registration_id'], 'Organizer verified attendance for registration ID: ' . $d['registration_id']);
        return $this->json(['status'=>'success','message'=>'Xac nhan diem danh thanh cong!']);
    }

    
    // GET /api/registration/all
    public function all() {
        $user = $this->requireCurrentUser();
        if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') {
            return $this->json(['status'=>'error', 'message'=>'Chi Admin/Organizer moi co quyen xem tat ca'], 403);
        }
        $data = $this->regRepo->getAllRegistrations();
        return $this->json(['status'=>'success','data'=>$data]);
    }

    // PUT /api/registration/update?id=X
    public function update() {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') return $this->json(['status'=>'error','message'=>'Ban khong co quyen'],403);
        
        $id = $_GET['id'] ?? 0;
        $d = $this->getInputData();
        $allowed = ['event_id', 'user_id', 'status', 'registered_at', 'cancelled_at'];
        $update = array_intersect_key($d, array_flip($allowed));
        
        if (empty($update)) return $this->json(['status'=>'error','message'=>'Khong co gi cap nhat'],400);
        $this->regRepo->update($id, $update);
        $this->logAudit($user['id'], 'Update Registration', 'registrations', $id, 'Updated registration ID: ' . $id);
        return $this->json(['status'=>'success','message'=>'Cap nhat thanh cong']);
    }

    // DELETE /api/registration/delete?id=X
    public function delete() {
        if ($_SERVER['REQUEST_METHOD']!=='DELETE') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') return $this->json(['status'=>'error','message'=>'Ban khong co quyen'],403);
        
        $id = $_GET['id'] ?? 0;
        $this->regRepo->delete($id);
        $this->logAudit($user['id'], 'Delete Registration', 'registrations', $id, 'Deleted registration ID: ' . $id);
        return $this->json(['status'=>'success','message'=>'Da xoa']);
    }
}
