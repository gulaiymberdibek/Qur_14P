
<?php
session_start();
include '../header.php'; // Go one level up to include header.php from httpdocs
$db=new DBS();
$pdo=$db->getConnection();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id']; // Get the logged-in user's ID

// Fetch user data from the database based on the logged-in user's ID
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $userId);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate initial avatar letter
$avatarLetter = strtoupper(substr($user['email'], 0, 1));

// Handle Image Upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["profile_image"])) {
    $targetDir = "uploads/";
    $fileName = $userId . "_" . basename($_FILES["profile_image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {
        // Save image path to DB
        $updateStmt = $pdo->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :id");
        $updateStmt->bindParam(':profile_image', $fileName);
        $updateStmt->bindParam(':id', $userId);
        $updateStmt->execute();
        $user['profile_image'] = $fileName; // Update user data
    }
}

// Define profile image path
$profileImage = !empty($user['profile_image']) ? "uploads/" . $user['profile_image'] : null;

  // Fetch user posts from the database
    $postStmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = :user_id ORDER BY created_at DESC");
    $postStmt->bindParam(':user_id', $userId);
    $postStmt->execute();
    $posts = $postStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
	<style>
		body{
		background-color:#fff;
		}
.main-content {
	width:100%;
	height:100vh;
      flex:1;
	display:flex;
	flex-direction:column;
	background-color:#fff;

	
}

.profile-container {
	display:flex;
	background-color:#fff;
	align-items:center;
	gap:20px;
	width:100%;
	position:absolute;
	top:0;
	left:0;
	
}
		.user-profile-box{
		display:flex;
		}
		.user-profile-nav-header{
		display:flex;
		}
		
		.user-details{
		display:flex;
			flex-direction:column;
		}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #00838F; /* Adjusted to match the image */
    color: white;
    font-size: 36px;
    font-weight: bold;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
		.user-profile-box {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 150px; /* Push it below the profile-container */
    background-color: white;
    padding: 15px 0;
    border-top: 1px solid #ddd;
}

.user-profile-nav-header {
    display: flex;
    width: 100%;
    padding-bottom: 10px;
	position:absolute;
	left:0;
}

.user-profile-nav-header div {
    padding: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #555;
    cursor: pointer;
}

.user-profile-nav-header div:hover {
    color: #0077b6;
    border-bottom: 2px solid #0077b6;
}
		.user-profile-content{
		display:flex;
		flex-direction:column;
		height:100%;
		position:absolute;
		top:200px;
		overflow-y:auto;
		max-width:calc(100% - 250px);
		left:0;
		padding-bottom:300px;
		border-right:2px solid #ccc;
		transition:transform  0.3s ease-in-out;
		scrollbar-width: none;
		background-color:#f3f3f3;
		}
		
		.user-profile-post{
		display:flex;
		flex-direction:column;
		align-items:flex-start;
		padding:20px;
		margin:10px;
		border-radius:10px;
		background-color:#fff;
			
		
		}
		.user-profile-post-avatar{
		width:60px;
		height:50px;
		border-radius:50%;
		overflow:hidden;
		background-color:#00838F;
		justify-content:center;
		align-items:center;
		font-weight:bold;
		color:white;
		display:flex;
		background-size:contain;
			font-size:18px;
			
		}
		.user-profile-post-header{
		display:flex;
		flex-direction:row;
			align-items:center;
			gap:10px;
			width:100%;
			
		}
		.user-profile-post-content-img{
		object-fit:contain;
		background-size:contain;
		align-items:center;
		max-height:500px;
	   width:100%;
	   height:auto;
	background-color:#000;
		}
		.user-profile-text{
		display:block;
		  white-space: normal; /* Allows text to wrap */
    word-wrap: break-word; /* Ensures words wrap properly */
    overflow-wrap: break-word;
			width:100%;
		}
		.user-profile-user-content{
		display:block;
		width:100%;
		}

		
		@media (max-width: 768px) {
  
			.user-profile-post-content-img{
				max-height:300px;
			}
  
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 28px;
		
		
    }
			.user-profile-content{
			max-width:100%;
			}

    
		}


	</style>
</head>
<body>
<div class="content">
        <div class="main-content">
           <div class="profile-container">
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="avatar-upload" class="profile-avatar">
            <?php if ($profileImage): ?>
                <img src="<?= htmlspecialchars($profileImage); ?>" alt="Profile Image">
            <?php else: ?>
                <div class="avatar-placeholder"><?= $avatarLetter; ?></div>
            <?php endif; ?>
        </label>
      <input type="file" id="avatar-upload" name="profile_image" accept="image/*" hidden onchange="this.form.submit()">
    </form>

    <div class='user-details'>
        <h1><?= htmlspecialchars($user['name']); ?></h1>
        <p><?= htmlspecialchars($user['email']); ?></p>
        <div class='user-profile-category-select'>
            <p><span id="user-category"><?= htmlspecialchars($user['category'] ); ?></span></p>
            <button id="open-category-modal">Тақырыпты өзгерту</button>
        </div>
    </div>
</div>

			  <!-- Modal for Category Selection -->
    <div id="category-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Тақырыпты таңдаңыз</h3>
            <ul id="category-list">
                <li data-category="Денсаулық">Денсаулық</li>
                <li data-category="Спорт">Спорт</li>
                <li data-category="Тарих">Тарих</li>
                <li data-category="Әдебиет">Әдебиет</li>
                <li data-category="Музыка">Музыка</li>
                <li data-category="Технология">Технология</li>
				  <li data-category="Күнделікті өмір">Күнделікті өмір</li>
				  <li data-category="Карьера">Карьера</li>
				  <li data-category="Кино & Сериал">Кино & Сериал</li>
				  <li data-category="Ойындар">Ойындар</li>
				  <li data-category="Табиғат">Табиғат</li>
				  <li data-category="Шығармашылық">Шығармашылық</li>
				  <li data-category="Соңғы жаңалықтар">Соңғы жаңалықтар</li>
				  <li data-category="Тамақ">Тамақ</li>
				  <li data-category="Бизнес">Бизнес</li>
				  <li data-category="Cән мен сұлулық">Cән мен сұлулық</li>
				  <li data-category="Ғылым және математика">Ғылым және математика</li>
				  <li data-category="Психология">Психология</li>
				 <li data-category="Философия">Философия</li>
				 <li data-category="Тіл және культура">Тіл және культура</li>
				 <li data-category="Қарым-қатынас">Қарым-қатынас</li>
                <!-- Add all other categories here -->
            </ul>
        </div>
    </div>
			<div class='user-profile-box'>
			<div class='user-profile-nav-header'>
				<div class='user-profile-nav-header-questions' onclick="showSection('questions')">Сұрақтары</div>
				<div class='user-profile-nav-header-answers' onclick="showSection('answers')">Жауаптары</div>
				<div class='user-profile-nav-header-following' onclick="showSection('following')">Тіркелімдер</div>
					<div class='user-profile-nav-header-followers' onclick="showSection('followers')">Тіркелушілер</div>

				
			</div>
				   <div class="user-profile-content">
					   <div id="questions" class="profile-section">
        <?php foreach ($posts as $post): ?>
            <div   class="user-profile-post">
                <!-- User Avatar: Circle-shaped, use the first letter of the user's email -->
				<div class='user-profile-post-header'>
				   <div class="user-profile-post-avatar">
                    <?php if ($profileImage): ?>
                        <img src="<?= htmlspecialchars($profileImage); ?>" alt="Profile Image" class='user-profile-post-avatar-image' >
                    <?php else: ?>
                        <div class="avatar-placeholder"><?= $avatarLetter; ?></div>
                    <?php endif; ?>
					  
                </div>
				  <p class='user-profile-user-name'><strong><?php echo htmlspecialchars($user['name']); ?></strong> </p>
					<div class="post-options">
    <button type="button" class="options-button"><svg fill="#000000" transform= "rotate(90)"  height="20px" width="20px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  viewBox="0 0 32.055 32.055" xml:space="preserve" ><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M3.968,12.061C1.775,12.061,0,13.835,0,16.027c0,2.192,1.773,3.967,3.968,3.967c2.189,0,3.966-1.772,3.966-3.967 C7.934,13.835,6.157,12.061,3.968,12.061z M16.233,12.061c-2.188,0-3.968,1.773-3.968,3.965c0,2.192,1.778,3.967,3.968,3.967 s3.97-1.772,3.97-3.967C20.201,13.835,18.423,12.061,16.233,12.061z M28.09,12.061c-2.192,0-3.969,1.774-3.969,3.967 c0,2.19,1.774,3.965,3.969,3.965c2.188,0,3.965-1.772,3.965-3.965S30.278,12.061,28.09,12.061z"></path> </g> </g></svg></button>
    <div class="options-menu" >
        <form class="delete-post-form" data-post-id="<?php echo $post['id']; ?>">
            <button type="button" class="delete-button">Жою</button>
        </form>
    </div>
</div>
				</div>
            
         
                <!-- Post Content -->
                <div class="user-profile-user-content">
                   
					<p class='user-profile-text'><?php echo htmlspecialchars($post['content']); ?></p>
                    
                   
                </div>
				 <!-- Display Post Image if Available -->
                    <?php if ($post['image']): ?>
                        <img src="https://qur.kz/k/<?php echo htmlspecialchars($post['image']); ?>" class='user-profile-post-content-img' alt="Post Image" style="max-width: 100%; ">
                    <?php endif; ?>
                    
                    <p><small>Posted on: <?php echo $post['created_at']; ?></small></p>
	<!-- 3-dot menu -->



            </div>
        <?php endforeach; ?>
						   
    </div>
					   </div>
				
				
				
				
				 <!-- Content container where posts will be displayed -->
 
				
			</div>
			
        </div>
    </div>
<script>
    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.profile-section').forEach(section => {
            section.style.display = 'none';
        });

        // Show the selected section
        document.getElementById(sectionId).style.display = 'block';
    }
</script>
	<script>
	document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("category-modal");
    const openModalBtn = document.getElementById("open-category-modal"); 
    const closeModal = document.querySelector(".close");
    const categoryList = document.getElementById("category-list");

    if (openModalBtn) {
        openModalBtn.addEventListener("click", function () {
            modal.style.display = "flex";
        });
    }

    if (closeModal) {
        closeModal.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }

    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

    // Event delegation for category selection
    if (categoryList) {
        categoryList.addEventListener("click", function (event) {
            if (event.target.tagName === "LI") {
                let selectedCategory = event.target.getAttribute("data-category");

                // Send AJAX request to update_category.php
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "https://qur.kz/update_category.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        document.getElementById("user-category").textContent = selectedCategory;
                        modal.style.display = "none";
                    }
                };
                xhr.send("category=" + encodeURIComponent(selectedCategory));
            }
        });
    }
});

	</script>
	<style>
	.modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1000;
    left: 250px;

  max-width:100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
	overflow-y:auto;
	max-height:100%;
	margin:10px;
	width:100%;
	border:3px solid transparent;
	
}

.close {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 24px;
    cursor: pointer;
}

#category-list {
    list-style: none;
    padding: 0;
    display: flex; /* Makes items appear in a row */
    flex-wrap: wrap; /* Allows wrapping if too many items */
    gap: 10px; /* Space between items */
    justify-content: center;
	padding:10px;
	
}
.post-options {
    position: relative;
    display: inline-block;
	width:100%;
}

.options-button {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
	display:flex;
	flex-direction:column;
	position:absolute;
	right:0;
	bottom:0;
}
		

.options-menu {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
    right: 0;
    min-width: 60px;
    padding: 5px 0;
    z-index: 1000;
	justify-content:center;
	margin-top:20px;
	
}

.options-menu form {
    margin: 0;
	border-radius:50px;
}

.delete-button {
    width: 100%;
    border: none;
    background: none;
    text-align: left;
    cursor: pointer;
	font-weight:bold;
	color:#000;
	font-size:20px;
}

.delete-button:hover {
    background: #f5f5f5;
}
		
		
		.modal-content h3{
		font-size:30px;
		}

#category-list li {
  cursor:pointer;
 background-color:#ffffff;
border-radius:20px;
	border:1px solid #ccc;
	font-weight:bold;
	padding:12px 12px;
	transition:0.5s;
	
}

#category-list li:hover {
    
}
		#user-category{
		font-weight:bold;
		}
		#open-category-modal{
		background:none;
		border:none;
		font-weight:bold;
		color:#959595;
		cursor:pointer;
		}
		.user-profile-category-select{
		display:flex;
		flex-direction:row;
		gap:5px;
		align-items:center;
		}
		
		@media (max-width: 768px) {
			.modal{
			left:0px;
			
			}
		}
		.user-profile-post-avatar-image{
		  width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;}
		
	</style>
	<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".delete-button").forEach(button => {
        button.addEventListener("click", function () {
            const postId = this.closest(".delete-post-form").dataset.postId;

            if (!confirm("Are you sure you want to delete this post?")) return;

            fetch("delete.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.text(); // Read response as text first
            })
            .then(text => {
                if (!text) {
                    throw new Error("Empty response from server.");
                }
                return JSON.parse(text); // Convert to JSON
            })
            .then(data => {
                if (data.success) {
                    document.getElementById(`post-${postId}`).remove();
                } else {
                    alert("Failed to delete post: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error.message));
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".options-button").forEach(button => {
        button.addEventListener("click", function () {
            let menu = this.nextElementSibling;
            menu.style.display = menu.style.display === "flex" ? "none" : "flex";
        });
    });

    // Close menu when clicking outside
    document.addEventListener("click", function (event) {
        document.querySelectorAll(".options-menu").forEach(menu => {
            if (!menu.contains(event.target) && !menu.previousElementSibling.contains(event.target)) {
                menu.style.display = "none";
            }
        });
    });
});


</script>


</body>
</html>