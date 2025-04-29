<?php
require 'config.php'; // Include DBS class

class Header {
    private $db;
    private $userId;
    private $profileImage;
    private $avatarLetter;

    public function __construct(DBS $dbs) {
        $this->db = $dbs->getConnection();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->userId = $_SESSION['user_id'] ?? null;
        $this->profileImage = null;
        $this->avatarLetter = "U"; // Default avatar letter

        if ($this->userId) {
            $this->fetchUserData();
        }
    }

    private function fetchUserData() {
        $stmt = $this->db->prepare("SELECT email, profile_image FROM users WHERE id = :id");
        $stmt->bindParam(':id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->avatarLetter = strtoupper(substr($user['email'], 0, 1));
            $this->profileImage = $user['profile_image'] ?: null; // Ensure it's null if empty
        }
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getProfileImage() {
        return $this->profileImage;
    }

    public function getAvatarLetter() {
        return $this->avatarLetter;
    }
}

// Initialize Header
$db = new DBS();
$header = new Header($db);

$userId = $header->getUserId();
$profileImage = $header->getProfileImage();
$avatarLetter = $header->getAvatarLetter();
?>


<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Qur</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script>window.yaContextCb=window.yaContextCb||[]</script>

  <style>
    /* Reset and general styling */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: Arial, sans-serif;
      background-color: #f3f3f3;
    }

    /* Header container */
    .bar {
      position: fixed;
      z-index: 1;
      top: 0;
      left: 0;
      width: 100%;
      height: 70px;
      /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); */
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background-color: #ffffff;
      border-bottom: 2px solid #ccc; /* Bottom border */
    }

    /* Logo */
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      color:rgb(255,215,0);
    }
    .logo img {
      width: 50px;
      height: 50px;
    }
    .logo p {
      font-size: 1.5rem;
      font-weight: bold;
      font-family:Le Monde Courrier;
    }

    /* Search button */
    .search-container {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-grow: 1;
    }
    .search-container input {
      width: 100%;
      max-width: 400px;
      padding: 10px 20px;
      border: 1px solid #ccc;
      border-radius: 25px;
      outline: none;
      font-size: 1rem;
    }

    /* Navigation and Login */
    .nav-section {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .nav-links {
      display: flex;
      gap: 20px;
     
    }
    .nav-links a {
      text-decoration: none;
      font-size: 1rem;
      transition: color 0.3s;
      display:flex;
	  align-items:center;
      border-radius: 25px;
        color:#000;
        padding: 10px 10px;
      font-weight: bold;
    }
    .home {
      background-color: transparent;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5px;
    }

    /* Hamburger menu for small screens */
    .hamburger {
      display: none;
      
    }
   
.cheeseburger{
  display: none;
      flex-direction: column;
      gap: 5px;
      cursor: pointer;
}
.cheeseburger div{
  width: 25px; /* Width of bars */
    height: 3px; /* Height of bars */
    background-color: black; /* Change to any color */
    border-radius: 3px; /* Rounded edges */
}
    #search{
      display:none;
    }
    .go_back{
      background-color: transparent;
      display:none;
      border: none;
  cursor: pointer;
    }
    #loginsignup{
      background-color: rgb(255,215,0);
      color:#ffffff;
		cursor:pointer;
    }
    .menu{
      display:none;
      background-color: transparent;
  border: none;
  cursor: pointer;

    }
   
   /* Sidebar styles */
.sidebar {
  position: fixed;
  top: 70px; /* Below the header */
  left: 0;
  width: 250px;
  height: 100vh;
 background-color: #ffffff;
 border-right: 2px solid #ccc; /* Right border */
 /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); */
  padding: 20px;
  scrollbar-width: thin; 
  transition: transform 0.3s ease-in-out;


  
}
	   
.sidebar ul::-webkit-scrollbar {
  width: 5px;
 
}
.sidebar ul {
  list-style: none; /* Removes the dots */
  margin: 0; /* Removes default margin */
  overflow-y: auto;
  font-size:20px;
  max-height: calc(100vh - 80px); 
	display:flex;
	flex-direction:column;
	
	
 
}
.sidebar ul li{
  border-bottom: 2px solid #ccc; /* Right border */
  /* list-style: none; */
  cursor: pointer;
  gap:10px;
  margin-bottom: 10px;
 text-decoration:none;
}
	  .sidebar ul li a {
	  text-decoration:none;
		  color:#000;
	  }
	  

.content{
  position: fixed;
      top:70px;
      left:0;
      display:flex;
      flex-direction: row;
      width:100%;
      height:100vh;
      margin: auto;
      margin-top:20px;
      
    }
	
   
    .ads{
      display:none;
	 
    }

	  .nav-profile-text{
	  display:none;
	  font-size:18px;
	  }






    #loginDiv, #emailDiv , #registrationDiv {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 300px;
    background-color: white;
    padding: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
    border-radius: 10px;
    z-index:10;
  }

 

  
  
  .registration-content {
    text-align: center;
  }
  
  #loginDiv input, #emailDiv input, #registrationDiv input {
    width: 100%;
    margin: 10px 0;
    padding: 10px;
  }
  
  #loginDiv button , #emailDiv button , #registrationDiv button {
    width: 100%;
    padding: 10px;
    background-color: rgb(52,56,119);
    color: white;
    border: none;
    cursor: pointer;
    margin-top: 10px;
  }

  #closeButton {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
  }
  .nav-profile-link {
        text-decoration: none;
        display: flex;
        align-items: center;
	    
    }

    .nav-profile-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #00838F;
        color: white;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
    }

    .nav-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
	    #post-question-div {
        display: none; /* Initially hidden */
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
        width: 300px;
        z-index: 1000;
    }
	   /* Close Button (X) */
    #close-post-div {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
    }

    /* Larger Textarea */
  #post-question-div textarea {
        width: 100%;
        height: 150px; /* Increased height */
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 5px;
        resize: none;
    }

    /* File Input Styling */
    #post-question-div input[type="file"] {
        margin: 10px 0;
    }

    /* Post Button at Bottom Right */
   #post-question-div button[type="submit"] {
        display: block;
        margin-left: auto;
        background-color: #00838F;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
	
  

    @media (min-width: 820px) and (max-width: 1030px) {
      .nav-links a{
padding:10px 10px;
      }
			.ads{
		display:none;
		}
		.content{
		flex-direction:column;
		}
      
  /* Your styles here */
}

/* Responsive styles */
    @media (max-width: 768px) {
      .main-content{
        max-width:100%;
      }
		.content{
		flex-direction:column;
		}
		.ads{
		display:none;
		}
		.nav-links.active{
		align-items:center;
			
		}
      .sidebar {
    transform: translateX(-100%); /* Hide the sidebar */
  }
		.sidebar ul{
		padding-bottom:100px;
		}
	
  .sidebar.active {
    transform: translateX(0); /* Show the sidebar when active */
  }
      #search{
      display:flex;
    }
    .cheeseburger{
      display: flex;
    }
      .nav-links {
        display: none;
        flex-direction: column;
        gap: 15px;
        background-color: #ffffff;
        position: absolute;
        top: 171px;
		  transform:translate(50%,-50%);
		  
        right: 50%;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        min-width:80%;
	
      
      }
		.nav-links a{
		
			font-weight:normal;
		}
	
      .nav-links.active {
        display: flex;
        padding: 10px 40px;
        
      }

      .hamburger {
        display: flex;
      }

     
      .logo_name{
        display: none;
      }
      .search-container{
        display:none;
      }
      .menu{
        display:flex;
      }
      .content{
  position: fixed;
      top:70px;
      left:0;
    }
   
		.nav-profile-text {
		display:flex;
		margin:5px;
		}
    }
   
  </style>
</head>
<body>

<div class="bar">
 
 
  <!-- Logo -->
  <div class="logo">
    <div class="cheeseburger">
      <div></div>
      <div></div>
      <div></div>
     </div>
 
    <p class="logo_name">Qur</p>
  </div>


  <!-- Search -->
  <div class="search-container">
  <button class="go_back">
     <svg fill="#737373" height="30px" width="30px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 404.258 404.258" xml:space="preserve" stroke="#737373"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <polygon points="289.927,18 265.927,0 114.331,202.129 265.927,404.258 289.927,386.258 151.831,202.129 "></polygon> </g></svg>
      </button>
   
    <form class="search-form" method="GET">
        <input type="text" name="search" placeholder="Іздеу..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
       
    </form>
	  
	  
  </div>
 
  <!-- Navigation and Login -->
  <div class="nav-section">
	  <a href='https://qur.kz/'>
    <button class="home" id='home-button'>
        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="30px" height="30px" viewBox="0 0 50 50" id="home-icon"> 
          <path d="M 25 1.0507812 C 24.7825 1.0507812 24.565859 1.1197656 24.380859 1.2597656 L 1.3808594 19.210938 C 0.95085938 19.550938 0.8709375 20.179141 1.2109375 20.619141 C 1.5509375 21.049141 2.1791406 21.129062 2.6191406 20.789062 L 4 19.710938 L 4 46 C 4 46.55 4.45 47 5 47 L 19 47 L 19 29 L 31 29 L 31 47 L 45 47 C 45.55 47 46 46.55 46 46 L 46 19.710938 L 47.380859 20.789062 C 47.570859 20.929063 47.78 21 48 21 C 48.3 21 48.589063 20.869141 48.789062 20.619141 C 49.129063 20.179141 49.049141 19.550938 48.619141 19.210938 L 25.619141 1.2597656 C 25.434141 1.1197656 25.2175 1.0507812 25 1.0507812 z M 35 5 L 35 6.0507812 L 41 10.730469 L 41 5 L 35 5 z" stroke="black" stroke-width="2" ></path>
      </svg></button> </a>
    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 101 101" id="search" width="30px" height="30px">
      <path d="M63.3 59.9c3.8-4.6 6.2-10.5 6.2-17 0-14.6-11.9-26.5-26.5-26.5S16.5 28.3 16.5 42.9 28.4 69.4 43 69.4c6.4 0 12.4-2.3 17-6.2l20.6 20.6c.5.5 1.1.7 1.7.7.6 0 1.2-.2 1.7-.7.9-.9.9-2.5 0-3.4L63.3 59.9zm-20.4 4.7c-12 0-21.7-9.7-21.7-21.7s9.7-21.7 21.7-21.7 21.7 9.7 21.7 21.7-9.7 21.7-21.7 21.7z"></path>
    </svg>
    
    <nav class="nav-links">
		 <?php if ($userId): ?>
   <a href="https://qur.kz/k/user_profile.php" class="nav-profile-link">
    <div class="nav-profile-avatar">
        <?php if (!empty($profileImage)): ?>
            <img src="https://qur.kz/k/uploads/<?= htmlspecialchars($profileImage); ?>" alt="Profile Image">
        <?php else: ?>
            <span><?= htmlspecialchars($avatarLetter); ?></span>
        <?php endif; ?>
    </div>
	   <span class="nav-profile-text" style='display:flex;flex-direction:row;align-items:center;'><strong>Жеке парақша</strong> <svg fill="#000" height="25px" width="25px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 330 330" xml:space="preserve" stroke="#999999"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path id="XMLID_222_" d="M250.606,154.389l-150-149.996c-5.857-5.858-15.355-5.858-21.213,0.001 c-5.857,5.858-5.857,15.355,0.001,21.213l139.393,139.39L79.393,304.394c-5.857,5.858-5.857,15.355,0.001,21.213 C82.322,328.536,86.161,330,90,330s7.678-1.464,10.607-4.394l149.999-150.004c2.814-2.813,4.394-6.628,4.394-10.606 C255,161.018,253.42,157.202,250.606,154.389z"></path> </g></svg></span>
</a>


    <?php else: ?>
       <a  id="loginsignup">Кіру/Тіркелу</a>
    <?php endif; ?>
       <a href="#post" id="post-question-btn">Сұрақ & Дәйексөз қалдыр</a>
		

    </nav>
    <div class="hamburger">
     <div class="nav-profile-avatar">
          <?php if ($profileImage): ?>
            <img src="https://qur.kz/k/uploads/<?= htmlspecialchars($profileImage); ?>" alt="Profile Image">
          <?php else: ?>
            <span><?= htmlspecialchars($avatarLetter); ?></span>
          <?php endif; ?>
        </div>
    </div>
  </div>
</div>
<div id="loginDiv">
  <div class="registration-content">
    <span id="closeButton">&times;</span>
    <h2>Кіру</h2>
	  
        <input type="email" id='login_email' placeholder="Email" required>
        <input type="password"id='login_password' placeholder="Пароль" required>
        <button type="submit" id="loginBtn"  onclick="loginUser()" >Кіру</button>
   <p id="message"></p>
	
    <div>Нет аккаунта?<a href="#signup" id="signup">Тіркелу</a></div>
  </div>
</div>


<div id="emailDiv">
  <input type="email" id="email" placeholder="Email">
  <button onclick="sendVerificationCode()">Отправить код верификации</button>
</div>

<div id="registrationDiv" style="display:none;">
  <input type="text" id="name" placeholder="Ваше имя">
  <input type="password" id="password" placeholder="Создайте пароль">
  <input type="text" id="verificationCode" placeholder="Введите код верификации.." oninput="this.value = this.value.trim()">
  <button onclick="registerUser()">Тіркелу</button>
</div>
	
	<div id="post-question-div">
		 <span id="close-post-div">&times;</span> <!-- X Close Button -->
    <form action="https://qur.kz/k/make_post.php" method="POST"  enctype="multipart/form-data">
        <textarea name="content" required placeholder="Жазба не сұрақ жазыңыз...."></textarea>
		<label for="imageUpload">
   <svg class='upload-btn' fill="gold" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 45.964 45.964" xml:space="preserve" stroke="#696969"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M7.071,30.834V11.062H4.042C1.803,11.062,0,12.893,0,15.13v26.732c0,2.24,1.803,4.051,4.042,4.051h26.733 c2.238,0,4.076-1.811,4.076-4.051v-2.92H15.179C10.733,38.943,7.071,35.281,7.071,30.834z"></path> <path d="M41.913,0.05H15.179c-2.238,0-4.066,1.813-4.066,4.051v26.733c0,2.241,1.829,4.067,4.066,4.067h26.734 c2.237,0,4.051-1.826,4.051-4.067V4.102C45.964,1.862,44.15,0.05,41.913,0.05z M41.363,28.589 c-0.223,0.412-0.652,0.656-1.12,0.656H17.336c-0.403,0-0.782-0.18-1.022-0.502c-0.24-0.324-0.313-0.736-0.197-1.123l3.277-10.839 c0.216-0.713,0.818-1.24,1.554-1.361c0.736-0.12,1.476,0.19,1.908,0.797l4.582,6.437c0.617,0.867,1.812,1.082,2.689,0.484 l4.219-2.865c0.434-0.295,0.967-0.402,1.48-0.299c0.515,0.102,0.966,0.408,1.253,0.848l4.229,6.472 C41.564,27.687,41.585,28.179,41.363,28.589z"></path> </g> </g> </g></svg>
</label>
<input type="file" id="imageUpload" name="image" accept="image/*" class="file-upload">

<!-- Music Upload -->
<label for="musicUpload">
  <svg class='upload-btn' height="20px" width="20px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 496.16 496.16" xml:space="preserve" fill="#ededed" stroke="#ededed" stroke-width="0.00496158"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="4.9615800000000005"></g><g id="SVGRepo_iconCarrier"> <path style="fill:#ffd500;" d="M496.158,248.085c0-137.022-111.068-248.082-248.074-248.082C111.07,0.003,0,111.063,0,248.085 c0,137.001,111.07,248.07,248.084,248.07C385.09,496.155,496.158,385.086,496.158,248.085z"></path> <g> <path style="fill:#635ba4;" d="M73.412,251.075c0,3.313-2.686,6-6,6H56.746c-3.314,0-6-2.687-6-6v-6.5c0-3.313,2.686-6,6-6h10.666 c3.314,0,6,2.687,6,6V251.075z"></path> <path style="fill:#635ba4;" d="M104.412,266.825c0,3.313-2.686,6-6,6H87.746c-3.314,0-6-2.687-6-6v-37.5c0-3.313,2.686-6,6-6 h10.666c3.314,0,6,2.687,6,6V266.825z"></path> </g> <g> <path style="fill:#615699;" d="M135.412,274.579c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-53c0-3.313,2.686-6,6-6h10.666 c3.314,0,6,2.687,6,6V274.579z"></path> <path style="fill:#615699;" d="M166.412,290.079c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-84c0-3.313,2.686-6,6-6h10.666 c3.314,0,6,2.687,6,6V290.079z"></path> </g> <g> <path style="fill:#635ba4;" d="M197.412,321.079c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-146c0-3.313,2.686-6,6-6 h10.666c3.314,0,6,2.687,6,6V321.079z"></path> <path style="fill:#635ba4;" d="M228.412,336.579c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-177c0-3.313,2.686-6,6-6 h10.666c3.314,0,6,2.687,6,6V336.579z"></path> </g> <path style="fill:#3e448e;" d="M259.412,383.079c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-270c0-3.313,2.686-6,6-6h10.666 c3.314,0,6,2.687,6,6V383.079z"></path> <g> <path style="fill:#635ba4;" d="M290.412,321.079c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-146c0-3.313,2.686-6,6-6 h10.666c3.314,0,6,2.687,6,6V321.079z"></path> <path style="fill:#635ba4;" d="M321.412,290.079c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-84c0-3.313,2.686-6,6-6h10.666 c3.314,0,6,2.687,6,6V290.079z"></path> </g> <g> <path style="fill:#615699;" d="M352.412,305.579c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-115c0-3.313,2.686-6,6-6 h10.666c3.314,0,6,2.687,6,6V305.579z"></path> <path style="fill:#615699;" d="M383.412,274.575c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-53c0-3.313,2.686-6,6-6h10.666 c3.314,0,6,2.687,6,6V274.575z"></path> </g> <g> <path style="fill:#635ba4;" d="M445.412,251.261c0,3.314-2.686,6-6,6h-10.666c-3.314,0-6-2.686-6-6v-6.5c0-3.313,2.686-6,6-6 h10.666c3.314,0,6,2.687,6,6V251.261z"></path> <path style="fill:#635ba4;" d="M414.412,259.079c0,3.313-2.686,6-6,6h-10.666c-3.314,0-6-2.687-6-6v-22c0-3.313,2.686-6,6-6h10.666 c3.314,0,6,2.687,6,6V259.079z"></path> </g> </g></svg>

</label>
<input type="file" id="musicUpload" name="music" accept="audio/*" class="file-upload">
        <button type="submit">Салу</button>
    </form>
    
</div>

<div class="content">
 <div class="main-content">
 
 </div>
 
  
</div>

<script>
  // Toggle mobile menu
  const hamburger = document.querySelector('.hamburger');
  const navLinks = document.querySelector('.nav-links');

  hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
  });
  const search_icon = document.getElementById('search');
const search_container = document.querySelector('.search-container');
const go_back = document.querySelector('.go_back');

search_icon.addEventListener('click', () => {
  search_container.style.display = 'flex';
  search_container.style.position = 'absolute';
  search_container.style.top = '0';
  search_container.style.left = '0';
  search_container.style.width = '100%';
  search_container.style.height = '70px';
  search_container.style.backgroundColor = '#ffffff';
  search_container.style.zIndex = '2';
  go_back.style.display='flex';
});
go_back.addEventListener('click',()=>{
  search_container.style.display = 'none';
})
document.querySelector(".cheeseburger").addEventListener("click", function() {
    document.getElementById("sidebar").classList.toggle("active");
   

  });
 document.getElementById("loginsignup").addEventListener("click", function() {
    document.getElementById("loginDiv").style.display = "block";
  });
document.getElementById('signup').addEventListener('click',function(){
  document.getElementById('emailDiv').style.display = 'block';
  document.getElementById('loginDiv').style.display = 'none';
})
  document.getElementById("closeButton").addEventListener("click", function() {
    document.getElementById("loginDiv").style.display = "none";
  });

  document.querySelectorAll(".nav-links a").forEach(link => {
    link.addEventListener("click", function () {
      navLinks.classList.remove('active'); // Hide nav links
    });
  });
 
 
</script>
<script>
        // Step 1: Send the verification code
        function sendVerificationCode() {
            var email = document.getElementById('email').value;
            
            if (email === "") {
                alert("Please enter an email");
                return;
            }

            $.ajax({
                url: 'k/send_verification_code.php',
                method: 'POST',
                data: { email: email },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === "success") {
                        alert(data.message);
                        // Store email and verification code in session
                        sessionStorage.setItem("email", email);
                        sessionStorage.setItem("verificationCode", data.verificationCode);
                        document.getElementById('emailDiv').style.display = 'none';
                        document.getElementById('registrationDiv').style.display = 'block';
                    } else {
                        alert(data.message);
                    }
                }
            });
        }

        // Step 2: Register the user
        function registerUser() {
            var name = document.getElementById('name').value;
            var password = document.getElementById('password').value;
            var verificationCode = document.getElementById('verificationCode').value;

            if (name === "" || password === "" || verificationCode === "") {
                alert("All fields are required");
                return;
            }

            var email = sessionStorage.getItem("email");
            var verificationCodeFromSession = sessionStorage.getItem("verificationCode");

            // Send registration data along with the verification code
            $.ajax({
                url: 'k/register_user.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    name: name,
                    password: password,
                    verificationCode: verificationCode
                }),
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === "success") {
                     window.location.href = "https://qur.kz/k/user_profile.php"; 
						exit();
                    } else {
                        alert(data.message);
                    }
                }
            });
        }
function loginUser() {
    var email = document.getElementById('login_email').value;
    var password = document.getElementById('login_password').value;

    $.ajax({
        url: 'k/login.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ email: email, password: password }),
        dataType: 'json', // Add this line to automatically parse the JSON response
        success: function(response) {
            console.log("Response:", response);
            alert(response.message); // response.message should contain the message from your PHP
		    // Redirect only if the status is success
    if (response.status === "success" && response.redirect_url) {
        window.location.href = response.redirect_url;
    }
			 
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            console.error("Response Text:", xhr.responseText);
            alert("Server error, please try again");
        }
    });
	
}


    </script>
 <!-- JavaScript to Handle Button Click -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const postBtn = document.getElementById("post-question-btn");
    const postDiv = document.getElementById("post-question-div");
   const closeBtn = document.getElementById("close-post-div");



    // Show post div when button is clicked
    postBtn.addEventListener("click", function(event) {
        event.preventDefault();
        postDiv.style.display = "block";
    });
  // Hide post div when close button is clicked
    closeBtn.addEventListener("click", function() {
        postDiv.style.display = "none";
    });


    
});
	document.addEventListener("DOMContentLoaded", function () {
        const homeIcon = document.querySelector("#home-icon path"); // Select the path inside the SVG
        if (window.location.href === "https://qur.kz/") {
            homeIcon.setAttribute("fill", "RGB(52, 56, 119)"); // Change the fill color to gold
			
        } else {
            homeIcon.setAttribute("fill", "white"); // Default color
        }
    });
</script>
	<script>
document.addEventListener("DOMContentLoaded", function() {
    let searchInput = document.getElementById("search-input");

    searchInput.addEventListener("keyup", function() {
        let query = searchInput.value.trim();
        window.location.href = "index.php?search=" + encodeURIComponent(query);
    });
});
</script>
	<style>
    .file-upload {
        display: none; /* Hide the default file input */
    }

    .upload-btn {
        cursor: pointer;
        width: 100px;
        height: 50px;
    }
</style>

</body>
</html>
