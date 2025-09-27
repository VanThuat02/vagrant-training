<?php
require_once 'BaseModel.php';

class UserModel extends BaseModel
{

    // Tìm user theo ID - Vá SQLi bằng prepared statement
    public function findUserById($id)
    {
        $sql = 'SELECT * FROM users WHERE id = ?'; // Placeholder ? để tránh nối chuỗi trực tiếp
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('i', $id); // 'i' cho kiểu integer
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $user;
    }

    // Tìm user theo từ khóa - Vá SQLi bằng prepared statement
    public function findUser($keyword)
    {
        $keyword = "%$keyword%"; // Thêm % cho tìm kiếm LIKE
        $sql = 'SELECT * FROM users WHERE user_name LIKE ? OR user_email LIKE ?'; // Placeholder cho keyword
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ss', $keyword, $keyword); // 's' cho string
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $user;
    }

    /**
     * Xác thực user - Vá SQLi, giữ nguyên md5 để tương thích dữ liệu cũ
     * @param $userName
     * @param $password
     * @return array
     */
    public function auth($userName, $password)
    {
        $md5Password = md5($password); // Mã hóa mật khẩu bằng md5 để khớp với dữ liệu cũ
        $sql = 'SELECT * FROM users WHERE name = ? AND password = ?'; // Placeholder cho username và password
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ss', $userName, $md5Password); // 's' cho string
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($user)) { // Trả về user nếu tìm thấy
            return $user;
        }
        return []; // Trả về rỗng nếu không khớp
    }

    /**
     * Xóa user theo ID - Vá SQLi bằng prepared statement
     * @param $id
     * @return mixed
     */
    public function deleteUserById($id)
    {
        $sql = 'DELETE FROM users WHERE id = ?'; // Placeholder cho ID
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('i', $id); // 'i' cho integer
        $stmt->execute();
        $result = $stmt->affected_rows; // Số hàng bị xóa
        $stmt->close();
        return $result;
    }

    /**
     * Cập nhật user - Vá SQLi, giữ md5 cho mật khẩu
     * @param $input
     * @return mixed
     */
    public function updateUser($input)
    {
        $sql = 'UPDATE users SET name = ?, password = ? WHERE id = ?'; // Placeholder cho name, password, id
        $stmt = self::$_connection->prepare($sql);
        $md5Password = md5($input['password']); // Mã hóa mật khẩu bằng md5
        $stmt->bind_param('ssi', $input['name'], $md5Password, $input['id']); // 's' cho string, 'i' cho integer
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();
        return $result;
    }

    /**
     * Thêm user - Vá SQLi, giữ md5 cho mật khẩu
     * @param $input
     * @return mixed
     */
    public function insertUser($input)
    {
        $sql = "INSERT INTO `app_web1`.`users` (`name`, `password`) VALUES (?, ?)"; // Placeholder cho name, password
        $stmt = self::$_connection->prepare($sql);
        $md5Password = md5($input['password']); // Mã hóa mật khẩu bằng md5
        $stmt->bind_param('ss', $input['name'], $md5Password); // 's' cho string
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();
        return $result;
    }

    /**
     * Tìm kiếm users - Vá SQLi, loại bỏ multi_query nguy hiểm
     * @param array $params
     * @return array
     */
    public function getUsers($params = [])
    {
        if (!empty($params['keyword'])) {
            $keyword = "%{$params['keyword']}%"; // Thêm % cho LIKE
            $sql = 'SELECT * FROM users WHERE name LIKE ?'; // Placeholder cho keyword, loại bỏ multi_query
            $stmt = self::$_connection->prepare($sql);
            $stmt->bind_param('s', $keyword); // 's' cho string
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $sql = 'SELECT * FROM users'; // Truy vấn đơn giản
            $stmt = self::$_connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        return $users;
    }
}