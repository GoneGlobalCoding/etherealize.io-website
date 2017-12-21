<?php

class Email {
    protected $status = false;
    protected $expectedKeys = ["name","email","category","subject","message"];
    protected $validateKeys = ['email'];
    protected $email;
    protected $name;
    protected $category;
    protected $subject;
    protected $message;
    protected $headers;
    const ADMIN_EMAIL = "admin@etherealize.io";
    
    public function __construct($payload){
        if(($msg = $this->validateRequest($payload, $this->expectedKeys)) !== true){
            throw new \Exception($msg);
        }
        $this->email = $payload['email'];
        $this->validateEmail($this->email);
        $this->message = $payload['message'];
        $this->subject = $payload['subject'];
        $this->name = $payload['name'];
        $this->category = $payload['category'];            
    }

    protected function validateEmail($email){
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception ("The email you entered is not valid. Please change to valid email");
        }
    }

    public function getEmailBody(){
        $body = "
        <html>
        <head>
        <title>HTML email</title>
        </head>
        <body> " . $this->message . "
        </body>
        </html>
        ";
        return $body;
    }

    public function getEmailHeaders(){
         // Always set content-type when sending HTML email
         $headers = "MIME-Version: 1.0" . "\r\n";
         $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
         // More headers
         $headers .= 'From: <' . self::ADMIN_EMAIL . '> ' . "\r\n";
         $headers .= 'Cc: <' . $this->email . '> ' . "\r\n";
         return $headers;
    }
     /**
     * Helper: for loop to check if keys exist in target array
     *
     * @param array $params
     * @param array $requiredFields
     * @return bool|string
     */
     protected function validateRequest($params, $requiredFields)
     {
         $missingFields = [];
         foreach ($requiredFields as $f) {
             if (!isset($params[$f])) {
                 $missingFields[] = $f;
             }
         }
         if (sizeof($missingFields) === 0) {
             return true;
         }
         return 'Missing fields ' . implode(', ', $missingFields) . '. ';
     }

    public function sendEmail(){
        $email = $this->email;
        $subject = $this->subject;
        $message = $this->getEmailBody();
        $headers = $this->getEmailHeaders();
        if(mail(self::ADMIN_EMAIL,$subject,$message,$headers)){
            $this->status = true;
        } else {
            throw new \Exception("Mail failed to send");
        }
    }

    public function getStatus(){
        return $this->status;
    }
}

class Json {
    public function __construct($response,$code=200){
        $status = $this->setResponseCode($code);
        header('Content-Type: application/json');
        switch($status){
            case "success":
                echo json_encode(["status"=>$status,"response"=>$response]);    
                break;
            case "error":
                echo json_encode(["status"=>$status,"response"=>$response->getMessage()]);
                break;
            default:
                echo json_encode(["status"=>"error","response"=>"null"]);
                break;
        }
    }
    
    public function setResponseCode($code){
        $status = null;
        switch($code){  
            case 200:
                header("HTTP/1.0 200 Ok");
                $status = "success";
                break;
            case 400:
                header("HTTP/1.0 400 Bad request");
                $status = "error";
                break;
            case 404:
                header("HTTP/1.0 404 Not Found");
                $status = "error";
                break;
            default:
                $status = "error";
                break;
        }
        return $status;
    }
}
/**
 * GET POST VARIABLES FROM FORM
 */
function main(){
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $email = new Email($_POST);
            $email->sendEmail();
            return new Json($email->getStatus());
        } catch (\Exception $e){
            return new Json($e,400);
        }
    } else {
        return new Json($response,400);
    }
}
/**
 * RUN MAIN FUNCTION
 */
main();
?>