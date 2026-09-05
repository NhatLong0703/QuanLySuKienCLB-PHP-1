<?php
class ClubMemberRepository extends BaseRepository {
    
    public function requestJoin($clubId, $userId) {
        $stmt = $this->db->prepare("INSERT IGNORE INTO club_members (club_id, user_id, status) VALUES (:club_id, :user_id, 'pending')");
        $stmt->execute(['club_id' => $clubId, 'user_id' => $userId]);
        return $this->db->lastInsertId();
    }

    public function updateStatus($clubId, $userId, $status) {
        $stmt = $this->db->prepare("UPDATE club_members SET status = :status, updated_at = NOW() WHERE club_id = :club_id AND user_id = :user_id");
        $stmt->execute(['status' => $status, 'club_id' => $clubId, 'user_id' => $userId]);
        return $stmt->rowCount();
    }

    public function leave($clubId, $userId) {
        $stmt = $this->db->prepare("DELETE FROM club_members WHERE club_id = :club_id AND user_id = :user_id");
        $stmt->execute(['club_id' => $clubId, 'user_id' => $userId]);
        return $stmt->rowCount();
    }

    public function findByClub($clubId) {
        $stmt = $this->db->prepare("
            SELECT cm.*, u.full_name, u.email, u.phone 
            FROM club_members cm 
            JOIN users u ON u.id = cm.user_id 
            WHERE cm.club_id = :club_id
            ORDER BY cm.joined_at DESC
        ");
        $stmt->execute(['club_id' => $clubId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findByUser($userId) {
        $stmt = $this->db->prepare("
            SELECT cm.*, c.name as club_name, c.image as club_image 
            FROM club_members cm 
            JOIN clubs c ON c.id = cm.club_id 
            WHERE cm.user_id = :user_id
            ORDER BY cm.joined_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMembershipStatus($clubId, $userId) {
        $stmt = $this->db->prepare("SELECT status FROM club_members WHERE club_id = :club_id AND user_id = :user_id");
        $stmt->execute(['club_id' => $clubId, 'user_id' => $userId]);
        return $stmt->fetchColumn();
    }
}
