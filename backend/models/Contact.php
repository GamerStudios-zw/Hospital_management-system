<?php
class Contact {
    private $conn;
    private $table_name = "contact_inquiries";

    public $name;
    public $email;
    public $organization;
    public $phone;
    public $message;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name,
                      email=:email,
                      organization=:org,
                      phone=:phone,
                      message=:msg";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->organization = htmlspecialchars(strip_tags($this->organization));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->message = htmlspecialchars(strip_tags($this->message));

        // Bind
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":org", $this->organization);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":msg", $this->message);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>