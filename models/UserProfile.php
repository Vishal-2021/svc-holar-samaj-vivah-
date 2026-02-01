<?php

class UserProfile {
    private $db;
 
    public $profile_id;
    public $photo_url;
    public $is_profile_photo;

    public $profileId;
    public $userId;
    public $createdFor;       // e.g., Self, Son, Daughter
    public $fullName;
    public $gender;
    public $maritalStatus;    // e.g., Single, Divorced, Widowed
    public $dateOfBirth;
    public $birthTime;
    public $birthDay;
    public $birthPlace;
    public $heightFeet;
    public $heightInches;
    public $weightKg;
    public $complexion;
    public $bloodGroup;
    public $education;
    public $job;
    public $annualIncome;
    public $fatherName;
    public $fatherJob;
    public $motherName;
    public $motherJob;
    public $nativePlace;
    public $currentAddress;
    public $otherRelatives;
    public $expectations;
    public $createdAt;
    public $updatedAt;
    public $status = 'InActive';
    
    

    public function __construct($db) {
        $this->db = $db;
    }


    // Create the user profile
  public function createProfile() {
    try {
  $query = "INSERT INTO profiles (
            user_id, created_for, full_name, gender, marital_status,
            date_of_birth, birth_time, birth_day, birth_place,
            height_feet, height_inches, weight_kg, complexion,
            blood_group, education, job, annual_income,
            father_name, father_job, mother_name, mother_job,
            native_place, current_address, other_relatives, expectations, status
        ) VALUES (
            :user_id, :created_for, :full_name, :gender, :marital_status,
            :date_of_birth, :birth_time, :birth_day, :birth_place,
            :height_feet, :height_inches, :weight_kg, :complexion,
            :blood_group, :education, :job, :annual_income,
            :father_name, :father_job, :mother_name, :mother_job,
            :native_place, :current_address, :other_relatives, :expectations, :status
        )
        ON DUPLICATE KEY UPDATE
            created_for = VALUES(created_for),
            full_name = VALUES(full_name),
            gender = VALUES(gender),
            marital_status = VALUES(marital_status),
            date_of_birth = VALUES(date_of_birth),
            birth_time = VALUES(birth_time),
            birth_day = VALUES(birth_day),
            birth_place = VALUES(birth_place),
            height_feet = VALUES(height_feet),
            height_inches = VALUES(height_inches),
            weight_kg = VALUES(weight_kg),
            complexion = VALUES(complexion),
            blood_group = VALUES(blood_group),
            education = VALUES(education),
            job = VALUES(job),
            annual_income = VALUES(annual_income),
            father_name = VALUES(father_name),
            father_job = VALUES(father_job),
            mother_name = VALUES(mother_name),
            mother_job = VALUES(mother_job),
            native_place = VALUES(native_place),
            current_address = VALUES(current_address),
            other_relatives = VALUES(other_relatives),
            expectations = VALUES(expectations),
            status = VALUES(status)";

        $stmt = $this->db->prepare($query);

        // Bind parameters
        $stmt->bindParam(':user_id', $this->userId);
        $stmt->bindParam(':created_for', $this->createdFor);
        $stmt->bindParam(':full_name', $this->fullName);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':marital_status', $this->maritalStatus);
        $stmt->bindParam(':date_of_birth', $this->dateOfBirth);
        $stmt->bindParam(':birth_time', $this->birthTime);
        $stmt->bindParam(':birth_day', $this->birthDay);
        $stmt->bindParam(':birth_place', $this->birthPlace);
        $stmt->bindParam(':height_feet', $this->heightFeet);
        $stmt->bindParam(':height_inches', $this->heightInches);
        $stmt->bindParam(':weight_kg', $this->weightKg);
        $stmt->bindParam(':complexion', $this->complexion);
        $stmt->bindParam(':blood_group', $this->bloodGroup);
        $stmt->bindParam(':education', $this->education);
        $stmt->bindParam(':job', $this->job);
        $stmt->bindParam(':annual_income', $this->annualIncome);
        $stmt->bindParam(':father_name', $this->fatherName);
        $stmt->bindParam(':father_job', $this->fatherJob);
        $stmt->bindParam(':mother_name', $this->motherName);
        $stmt->bindParam(':mother_job', $this->motherJob);
        $stmt->bindParam(':native_place', $this->nativePlace);
        $stmt->bindParam(':current_address', $this->currentAddress);
        $stmt->bindParam(':other_relatives', $this->otherRelatives);
        $stmt->bindParam(':expectations', $this->expectations);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            return $this->db->lastInsertId();  // Return the profile_id
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
 }

   
    // Upload Profile photo
    public function UploadUserPhoto() {
        try {
            $query = "INSERT INTO photos (profile_id, photo_url, is_profile_photo) 
                      VALUES (:profile_id, :photo_url, :is_profile_photo)";
            
            $stmt = $this->db->prepare($query); 
            $stmt->bindParam(':profile_id',  $this->profile_id);
            $stmt->bindParam(':photo_url',  $this->photo_url);
            $stmt->bindParam(':is_profile_photo',  $this->is_profile_photo, PDO::PARAM_BOOL);

            if ($stmt->execute()) {
                return $this->db->lastInsertId();  // Return the photo_id
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    

   // Get the user profile by user ID
    public function getProfileByUserId($user_id) {
        try {   
        $query = "SELECT * FROM profiles WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch (Exception $e) {
            return throw new Exception("Error fetching profile: " . $e->getMessage());
        }
    }


}
