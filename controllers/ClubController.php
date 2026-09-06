<?php
class ClubController extends BaseController {
    private $clubRepo;
    private $clubManagerRepo;
    private $clubMemberRepo;
    public function __construct() { 
        $this->clubRepo = new ClubRepository(); 
        $this->clubManagerRepo = new ClubManagerRepository();
        $this->clubMemberRepo = new ClubMemberRepository();
    }

    public function index() { 
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 6);
        $status = $_GET['status'] ?? null;
        if ($status === 'all') $status = null;
        
        $clubs = $this->clubRepo->getAll($page, $limit, $status);
        
        // If logged in, fetch membership status for each club
        $user = $this->getCurrentUser();
        if ($user) {
            foreach ($clubs['data'] as &$club) {
                $memStatus = $this->clubMemberRepo->getMembershipStatus($club['id'], $user['id']);
                $club['membership_status'] = $memStatus; // null, pending, approved, rejected
            }
        }
        
        return $this->json(['status'=>'success','data'=>$clubs]); 
    }

    public function show() {
        $c = $this->clubRepo->findById($_GET['id']??0);
        if (!$c) return $this->json(['status'=>'error','message'=>'Khong tim thay CLB'],404);
        return $this->json(['status'=>'success','data'=>$c]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD']!=='POST') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') return $this->json(['status'=>'error','message'=>'Ban khong co quyen'],403);
        
        $d = $this->getInputData();
        if (empty($d['name'])) return $this->json(['status'=>'error','message'=>'Thieu ten CLB'],400);
        
        $d['created_by'] = $user['id'];
        $d['status'] = $d['status'] ?? 'active';
        $d['description'] = $d['description'] ?? '';
        
        $imagePath = $this->uploadImage('image', 'clubs');
        if ($imagePath) $d['image'] = $imagePath;
        
        $id = $this->clubRepo->create($d);
        
        // Automatically assign the creator as a manager
        $this->clubManagerRepo->assign($id, $user['id']);
        
        $this->logAudit($user['id'], 'Create Club', 'clubs', $id, 'Created club: ' . $d['name']);
        return $this->json(['status'=>'success','data'=>$this->clubRepo->findById($id)],201);
    }

    public function update() {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        
        $id = $_GET['id'] ?? 0;
        $c = $this->clubRepo->findById($id);
        if (!$c) return $this->json(['status'=>'error','message'=>'Khong tim thay CLB'],404);

        if ($user['role'] !== 'admin') {
            if ($user['role'] !== 'organizer' || !$this->clubManagerRepo->isManager($id, $user['id'])) {
                return $this->json(['status'=>'error','message'=>'Ban khong co quyen tren CLB nay'],403);
            }
        }

        $d = $this->getInputData();
        $allowed = ['name','description','status'];
        $update = array_intersect_key($d, array_flip($allowed));
        
        $imagePath = $this->uploadImage('image', 'clubs');
        if ($imagePath) $update['image'] = $imagePath;
        
        $this->clubRepo->update($id, $update);
        $this->logAudit($user['id'], 'Update Club', 'clubs', $id, 'Updated club ID: ' . $id);
        return $this->json(['status'=>'success','data'=>$this->clubRepo->findById($id)]);
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD']!=='DELETE') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        
        $id = $_GET['id'] ?? 0;
        $c = $this->clubRepo->findById($id);
        if (!$c) return $this->json(['status'=>'error','message'=>'Khong tim thay CLB'],404);

        if ($user['role'] !== 'admin') {
            if ($user['role'] !== 'organizer' || !$this->clubManagerRepo->isManager($id, $user['id'])) {
                return $this->json(['status'=>'error','message'=>'Ban khong co quyen xoa CLB nay'],403);
            }
        }

        $this->clubRepo->delete($id);
        $this->logAudit($user['id'], 'Delete Club', 'clubs', $id, 'Deleted club ID: ' . $id);
        return $this->json(['status'=>'success','message'=>'Xoa CLB thanh cong']);
    }

    // --- CLUB MEMBERSHIP ENDPOINTS ---

    public function join() {
        if ($_SERVER['REQUEST_METHOD']!=='POST') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        $d = $this->getInputData();
        if (empty($d['club_id'])) return $this->json(['status'=>'error','message'=>'Thieu ID cau lac bo'],400);

        $club = $this->clubRepo->findById($d['club_id']);
        if (!$club) return $this->json(['status'=>'error','message'=>'CLB khong ton tai'],404);

        $status = $this->clubMemberRepo->getMembershipStatus($d['club_id'], $user['id']);
        if ($status) {
            return $this->json(['status'=>'error','message'=>'Ban da gui yeu cau hoac da la thanh vien'],400);
        }

        $this->clubMemberRepo->requestJoin($d['club_id'], $user['id']);
        $this->logAudit($user['id'], 'Join Club', 'club_members', $d['club_id'], 'Requested to join club: ' . $d['club_id']);
        return $this->json(['status'=>'success','message'=>'Da gui yeu cau tham gia. Vui long cho duyet.']);
    }

    public function members() {
        $user = $this->requireCurrentUser();
        $clubId = $_GET['club_id'] ?? 0;
        
        $members = $this->clubMemberRepo->findByClub($clubId);
        return $this->json(['status'=>'success','data'=>$members]);
    }

    public function pendingRequests() {
        $user = $this->requireCurrentUser();
        
        // Find all clubs managed by this user
        $managedClubs = $this->clubManagerRepo->findByUser($user['id']);
        
        $allPending = [];
        foreach ($managedClubs as $mc) {
            $members = $this->clubMemberRepo->findByClub($mc['club_id']);
            foreach ($members as $m) {
                if ($m['status'] === 'pending') {
                    $m['club_name'] = $mc['club_name'];
                    $allPending[] = $m;
                }
            }
        }
        
        return $this->json(['status'=>'success','data'=>$allPending]);
    }

    public function memberStatus() {
        if ($_SERVER['REQUEST_METHOD']!=='PUT') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') return $this->json(['status'=>'error','message'=>'Ban khong co quyen'],403);

        $d = $this->getInputData();
        if (empty($d['club_id']) || empty($d['user_id']) || empty($d['status'])) {
            return $this->json(['status'=>'error','message'=>'Thieu thong tin'],400);
        }
        
        $this->clubMemberRepo->updateStatus($d['club_id'], $d['user_id'], $d['status']);
        $this->logAudit($user['id'], 'Update Club Member', 'club_members', $d['club_id'], "Updated status to {$d['status']} for user {$d['user_id']}");
        return $this->json(['status'=>'success','message'=>'Cap nhat trang thai thanh cong']);
    }

    public function leave() {
        if ($_SERVER['REQUEST_METHOD']!=='DELETE') return $this->json(['message'=>'Method Not Allowed'],405);
        $user = $this->requireCurrentUser();
        $d = $this->getInputData();
        
        $clubId = $d['club_id'] ?? ($_GET['club_id'] ?? 0);
        $targetUserId = $d['user_id'] ?? ($_GET['user_id'] ?? $user['id']);
        
        // If someone is trying to remove someone else, check permissions
        if ($targetUserId != $user['id']) {
            if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') {
                return $this->json(['status'=>'error','message'=>'Ban khong co quyen xoa nguoi khac'],403);
            }
        }
        
        $this->clubMemberRepo->leave($clubId, $targetUserId);
        $this->logAudit($user['id'], 'Leave Club', 'club_members', $clubId, "User {$targetUserId} left club {$clubId}");
        return $this->json(['status'=>'success','message'=>'Da xoa thanh vien khoi CLB']);
    }
}
