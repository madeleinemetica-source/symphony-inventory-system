<?php
class User {
    private $conn;
    private $table = "users";

    public $user_id;
    public $full_name;
    public $email;
    public $password_hash;
    public $created_at;
    public $last_login;
    public $profile_picture;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT user_id, full_name, password_hash, profile_picture 
                  FROM " . $this->table . " 
                  WHERE email = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->user_id = $row['user_id'];
            $this->full_name = $row['full_name'];
            $this->password_hash = $row['password_hash'];
            $this->profile_picture = $row['profile_picture'];
            return true;
        }
        return false;
    }

    // Create user 
    public function create() {
        if($this->profile_picture) {
        $query = "INSERT INTO " . $this->table . " 
                 (full_name, email, password_hash, profile_picture, created_at) 
                 VALUES (:full_name, :email, :password_hash, :profile_picture, :created_at)";
    } else {
        $query = "INSERT INTO " . $this->table . " 
                 (full_name, email, password_hash, created_at) 
                 VALUES (:full_name, :email, :password_hash, :created_at)";
    }
        
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Hash password
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);
        
        // Bind
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":created_at", date('Y-m-d H:i:s'));

        if($this->profile_picture) {
        $stmt->bindParam(":profile_picture", $this->profile_picture);
        }

        return $stmt->execute();
    }

    // Update last login
    public function updateLastLogin() {
        $query = "UPDATE " . $this->table . " SET last_login = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        
        if($stmt->execute()) {
            //update last login property
            $this->last_login = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    public function updateProfile() {
        $query = "UPDATE " . $this->table . "SET full_name = :full_name, email = :email WHERE user_id = :user_id";

        $stmt = $this->conn->query($query);

        // sanitize
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // bind parameters
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateProfilePicture($user_id, $file) {
        if($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/pictures/';

            // create directory if it does not exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $fileName;

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

            if (in_array($file['type'], $allowedTypes) && move_uploaded_file($file['tmp_name'], $uploadFile)) {
                // Update database
                $query = "UPDATE " . $this->table . " SET profile_picture = :profile_picture WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":profile_picture", $uploadFile);
                $stmt->bindParam(":user_id", $user_id);
                
                if($stmt->execute()) {
                    $this->profile_picture = $uploadFile;
                    return true;
                }
            }
        }
        return false;
    }
}
?>