<?php
 
class DB_Functions {
 
    private $conn;
 
    // constructor
    function __construct() {
        require_once 'db_connect.php';
        // connecting to database
        $db = new Db_Connect();
        $this->conn = $db->connect();
    }
 
    // destructor
    function __destruct() {
         
    }
 
    /**
     * Storing new user
     * returns user details
     */
    public function storeUser($name,$number,$province,$district, $email, $password) {
        $uuid = uniqid('', true);
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt

        $sql = "INSERT INTO tbl_user(unique_id,name,number,province,district, email, encrypted_password, salt, created_at) VALUES(?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("ssssssss", $uuid, $name,$number,$province,$district, $email, $encrypted_password, $salt);

            $result = $stmt->execute();

            $stmt->close();
        }
        else {
            $error = $this->conn->errno . ' ' . $this->conn->error;
            echo $error; // 1054 Unknown column 'foo' in 'field list'
        }
 
        // check for successful store
        if ($result) {
            $stmt = $this->conn->prepare("SELECT * FROM tbl_user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
 
            return $user;
        } else {
            return false;
        }
    }

    /**
     * Image Upload
     */
    public function ImageUpload($image,$email) {
        $sql = "UPDATE tbl_user SET image=?,updated_at=NOW() WHERE email=?";

        if($stmt = $this->conn->prepare($sql))
        {
        $stmt->bind_param('ss', $image,$email);
        $result = $stmt->execute();
        
        $stmt->close();
        }
        else {
            $error = $this->conn->errno . ' ' . $this->conn->error;
            echo $error; // 1054 Unknown column 'foo' in 'field list'
        }

        // check
        if ($result) {
            $stmt = $this->conn->prepare("SELECT * FROM tbl_user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
 
            return true;
        } else {
            return false;
        }
    }
 
    /**
     * Get user by email and password
     */
    public function getUserByEmailAndPassword($email, $password) {
 
        $stmt = $this->conn->prepare("SELECT * FROM tbl_user WHERE email = ?");
 
        $stmt->bind_param("s", $email);
 
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
 
            // verifying user password
            $salt = $user['salt'];
            $encrypted_password = $user['encrypted_password'];
            $hash = $this->checkhashSSHA($salt, $password);
            // check for password equality
            if ($encrypted_password == $hash) {
                // user authentication details are correct
                return $user;
            }
        } else {
            return NULL;
        }
    }

     /**
     * Check user is existed or not
     */
    public function isUserExisted($email) {
        $sql = "SELECT email from tbl_user WHERE email = ?";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("s", $email);

            $stmt->execute();

            $stmt->store_result();
        }
        else {
            $error = $this->conn->errno . ' ' . $this->conn->error;
            echo $error; // 1054 Unknown column 'foo' in 'field list'
        }

        if ($stmt->num_rows > 0) {
            // user existed 
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }
 
    /**
     * Encrypting password
     * @param password
     * returns salt and encrypted password
     */
    public function hashSSHA($password) {
 
        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }
 
    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */
    public function checkhashSSHA($salt, $password) {
 
        $hash = base64_encode(sha1($password . $salt, true) . $salt);
 
        return $hash;
    }
 
}
 
?>