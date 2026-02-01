<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserProfile.php'; 
class UserController {
    private $db;
    private $userModel;
    private $userProfileModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
        $this->userProfileModel = new UserProfile($db);
    }

    // User Basic Registration 
    public function register($data) {
        $data = json_decode($data, true);

        if (empty($data['email']) || empty($data['password']) || empty($data['mobile_number'])) {
            echo json_encode(['message' => 'Email Address, Mobile Number and password are required']);
            return;
        }

        // Hash the password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $this->userModel->email = $data['email'];
        $this->userModel->mobile_number = $data['mobile_number'];
        $this->userModel->role = "user";
        $this->userModel->password_hash = $passwordHash;

        // Check if the email already exists
        if ($this->userModel->emailExists()) {
          http_response_code(400);  
          echo json_encode(['status' => 'FAILED', 'message' => 'Email already exists.']);
          return;
        }

        // Attempt to create the user
        $user_id = $this->userModel->createUser();
        if ($user_id) {
            echo json_encode(['user_id' => $user_id, 'status' => 'SUCCESS', 'message' => 'User created successfully.' ]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'FAILED', 'message' => 'An error occurred while creating the user. Please try again later.']);
        }

    }

 public function CreateProfile($data) {

   // Decode JSON string to array if necessary
    if (is_string($data)) {
        $data = json_decode($data, true); // true = convert to associative array
        if ($data === null) {
            http_response_code(400);
            echo json_encode([
                'status' => 'FAILED',
                'message' => 'Invalid JSON input.'
            ]);
            return;
        }
    }

    // Map request data to the profile model
    $this->userProfileModel->userId          = $data['userId'];
    $this->userProfileModel->createdFor      = $data['createdFor'] ?? 'Self';
    $this->userProfileModel->fullName        = $data['fullName'];
    $this->userProfileModel->gender          = $data['gender'];
    $this->userProfileModel->maritalStatus   = $data['maritalStatus'] ?? 'Single';
    $this->userProfileModel->dateOfBirth     = $data['dateOfBirth'] ?? null;
    $this->userProfileModel->birthTime       = $data['birthTime'] ?? null;
    $this->userProfileModel->birthDay        = $data['birthDay'] ?? null;
    $this->userProfileModel->birthPlace      = $data['birthPlace'] ?? null;
    $this->userProfileModel->heightFeet      = $data['heightFeet'] ?? null;
    $this->userProfileModel->heightInches    = $data['heightInches'] ?? null;
    $this->userProfileModel->weightKg        = $data['weightKg'] ?? null;
    $this->userProfileModel->complexion      = $data['complexion'] ?? null;
    $this->userProfileModel->bloodGroup      = $data['bloodGroup'] ?? null;
    $this->userProfileModel->education       = $data['education'] ?? null;
    $this->userProfileModel->job             = $data['job'] ?? null;
    $this->userProfileModel->annualIncome    = $data['annualIncome'] ?? null;
    $this->userProfileModel->fatherName      = $data['fatherName'] ?? null;
    $this->userProfileModel->fatherJob       = $data['fatherJob'] ?? null;
    $this->userProfileModel->motherName      = $data['motherName'] ?? null;
    $this->userProfileModel->motherJob       = $data['motherJob'] ?? null;
    $this->userProfileModel->nativePlace     = $data['nativePlace'] ?? null;
    $this->userProfileModel->currentAddress  = $data['currentAddress'] ?? null;
    $this->userProfileModel->otherRelatives  = $data['otherRelatives'] ?? null;
    $this->userProfileModel->expectations    = $data['expectations'] ?? null;
    $this->userProfileModel->status          = $data['status'] ?? 'Active';

    // Create profile
    $profileId = $this->userProfileModel->createProfile();

    if ($profileId) {
        // Assign role to user
        $this->userModel->userId = $data['userId'];
        $this->userModel->role = 'user';
        $this->userModel->updateRole();

        echo json_encode([
            'profileId' => $profileId,
            'status' => 'SUCCESS',
            'message' => 'Profile created successfully.'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'FAILED',
            'message' => 'An error occurred while creating the profile. Please try again later.'
        ]);
    }
}


    // Profile Photo Upload
  public function UploadProfilePhoto() {

    if (empty($_POST['user_id']) || empty($_FILES['profile_photo'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID and photo are required']);
        return;
    }

    $profileId = $_POST['user_id'];
    $file = $_FILES['profile_photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File upload error: ' . $file['error']]);
        return;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpeg', 'jpg', 'png'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid image format']);
        return;
    }

    $dir = __DIR__ . '/../uploads/profile_pictures/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create directory']);
        return;
    }

    $filename = "profile_{$profileId}_" . time() . ".$ext";
    $filePath = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        return;
    }

    // Save photo details in the database
    $this->userProfileModel->profile_id = $profileId;
    $this->userProfileModel->photo_url = '/uploads/profile_pictures/' . $filename;
    $this->userProfileModel->is_profile_photo = true;

    $photoId = $this->userProfileModel->UploadUserPhoto();
    if ($photoId) {
        // Photo uploaded successfully
        echo json_encode(['photoId' => $photoId, 'status' => 'SUCCESS', 'message' => 'Photo uploaded successfully.']);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'FAILED',
            'message' => 'An error occurred while uploading the Photo. Please try again later.'
        ]);
    }
}






    // User Login
    public function login($data) {
        $data = json_decode($data, true);
        if (empty($data['email']) || empty($data['password'])) {
            echo json_encode(['message' => 'Email and password are required']);
            return;
        }

        $this->userModel->email = $data['email'];

        // Check if the user exists
        $user = $this->userModel->getUserByEmail();
        if ($user && password_verify($data['password'], $user['password_hash'])) {
            // Simply return a success message as there's no JWT now
            echo json_encode(['message' => 'Login successful', 'role' => $user['role']]);
        } else {
            echo json_encode(['message' => 'Invalid credentials']);
        }
    }

    // Get User Profile
    public function getUserProfile($user_id) {
      
       $userProfile = $this->userProfileModel->getProfileByUserId($user_id);
        if ($userProfile) {
          echo json_encode($userProfile);
        } else {
          echo json_encode(['message' => 'User profile not found']);
        }
      
    }

    // Update User Profile
    public function updateProfile($data) {
        $data = json_decode($data, true);

        $user_id = $data['user_id'];
        $this->userProfileModel->first_name = $data['first_name'];
        $this->userProfileModel->last_name = $data['last_name'];
        $this->userProfileModel->gender = $data['gender'];
        $this->userProfileModel->date_of_birth = $data['date_of_birth'];

        if ($this->userProfileModel->updateProfile($user_id)) {
            echo json_encode(['message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['message' => 'Profile update failed']);
        }
    }

   // Serach User Profile
   public function searchProfiles($data) {
    $data = json_decode($data, true);

    $userProfile = $this->userModel->getUserProfiles($data['page'],$data['perpage']);

    if ($userProfile) {
        echo json_encode($userProfile);
    } else {
        echo json_encode(['status' => 'FAILED', 'message' => 'User not found.']);
    }
}


}
