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
            echo json_encode(['status' => 'FAILED', 'message' => 'Email Address, Mobile Number and password are required']);
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

         // Check if the mobile no already exists
        if ($this->userModel->mobileExists()) {
          http_response_code(400);  
          echo json_encode(['status' => 'FAILED', 'message' => 'Mobile no already exists.']);
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

      // ✅ REQUIRED FIELD
    if (!isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'FAILED',
            'message' => 'user_id is required'
        ]);
        return;
    }

    // Map request data to the profile model
    $this->userProfileModel->userId          = $data['user_id'];
    $this->userProfileModel->createdFor      = $data['created_for'] ?? 'Self';
    $this->userProfileModel->fullName        = $data['full_name'];
    $this->userProfileModel->gender          = $data['gender'];
    $this->userProfileModel->maritalStatus   = $data['marital_status'] ?? 'Single';
    $this->userProfileModel->dateOfBirth     = $data['date_of_birth'] ?? null;
    $this->userProfileModel->birthTime       = $data['birth_time'] ?? null;
    $this->userProfileModel->birthDay        = $data['birth_day'] ?? null;
    $this->userProfileModel->birthPlace      = $data['birth_place'] ?? null;
    $this->userProfileModel->heightFeet      = $data['height_feet'] ?? null;
    $this->userProfileModel->heightInches    = $data['height_inches'] ?? null;
    $this->userProfileModel->weightKg        = $data['weight_kg'] ?? null;
    $this->userProfileModel->complexion      = $data['complexion'] ?? null;
    $this->userProfileModel->bloodGroup      = $data['blood_group'] ?? null;
    $this->userProfileModel->education       = $data['education'] ?? null;
    $this->userProfileModel->job             = $data['job'] ?? null;
    $this->userProfileModel->annualIncome    = $data['annual_income'] ?? null;
    $this->userProfileModel->fatherName      = $data['father_name'] ?? null;
    $this->userProfileModel->fatherJob       = $data['father_job'] ?? null;
    $this->userProfileModel->motherName      = $data['mother_name'] ?? null;
    $this->userProfileModel->motherJob       = $data['mother_job'] ?? null;
    $this->userProfileModel->nativePlace     = $data['native_place'] ?? null;
    $this->userProfileModel->currentAddress  = $data['current_address'] ?? null;
    $this->userProfileModel->otherRelatives  = $data['other_relatives'] ?? null;
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
            echo json_encode([
                'status' => 'SUCCESS', 
                'message' => 'Login successful', 
                'user'=>[ 
                    'user_id' => $user['user_id'],
                    'email'   => $user['email'],
                    'role' => $user['role']
                    ]
                ]);
        } else {                
            echo json_encode(['status' => 'FAILED','message' => 'Invalid credentials']);
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
    //     public function searchProfiles($data) {
    //         $data = json_decode($data, true);

    //         $userProfile = $this->userModel->getUserProfiles($data['page'],$data['perpage']);

    //         if ($userProfile) {
    //             echo json_encode($userProfile);
    //         } else {
    //             echo json_encode(['status' => 'FAILED', 'message' => 'User not found.']);
    //         }
    //     }

    // Search User Profile (WITH FILTERS)
    public function searchProfiles($data) {

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $page     = $data['page'] ?? 1;
        $perPage  = $data['perPage'] ?? 8;

        $filters = [
            'gender'     => $data['gender'] ?? null,
            'location'   => $data['location'] ?? null,
            'education'  => $data['education'] ?? null,
            'profession' => $data['profession'] ?? null,
            // 'minAge'     => $data['minAge'] ?? null,
            // 'maxAge'     => $data['maxAge'] ?? null
        ];

        $result = $this->userModel->getUserProfiles($page, $perPage, $filters);

        echo json_encode($result);
    }


}
