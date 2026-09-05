-- 02_club_members.sql
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS club_members (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      BIGINT UNSIGNED NOT NULL,
    user_id      BIGINT UNSIGNED NOT NULL,
    status       ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    role_in_club VARCHAR(50) NOT NULL DEFAULT 'member',
    joined_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_club_user (club_id, user_id),
    CONSTRAINT fk_cm_club_id FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
