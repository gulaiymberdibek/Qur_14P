


<?php

session_start();

require '../header.php';
$db=new DBS();
$pdo=$db->getConnection();

// Get user ID from URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if (!$user_id) {
    die("Invalid user ID.");
}


// Fetch user details
$user_stmt = $pdo->prepare("SELECT name, email,profile_image FROM users WHERE id = :user_id");
$user_stmt->execute(['user_id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$logged_in_user_id = $_SESSION['user_id'] ?? null;

// Fetch followers count
$follower_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = :user_id");
$follower_stmt->execute(['user_id' => $user_id]);
$follower_count = $follower_stmt->fetchColumn();

// Check if the logged-in user is following this profile
$isFollowing = false;
if ($logged_in_user_id) {
    $follow_check_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
    $follow_check_stmt->execute(['follower_id' => $logged_in_user_id, 'followed_id' => $user_id]);
    $isFollowing = $follow_check_stmt->fetchColumn() > 0;
}



// Fetch posts by this user along with likes and comments count
$posts_stmt = $pdo->prepare("
    SELECT posts.*, 
           COALESCE(like_count.likes, 0) AS likes, 
           COALESCE(comment_count.total_comments, 0) AS comments
    FROM posts
    LEFT JOIN (SELECT post_id, COUNT(*) AS likes FROM likes GROUP BY post_id) AS like_count 
        ON posts.id = like_count.post_id
    LEFT JOIN (SELECT post_id, COUNT(*) AS total_comments FROM comments GROUP BY post_id) AS comment_count 
        ON posts.id = comment_count.post_id
    WHERE posts.user_id = :user_id
    ORDER BY posts.created_at DESC
");

// Prepare the statement to fetch comments
$comments_stmt = $pdo->prepare("
    SELECT comments.*, users.name, users.email ,users.profile_image
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.post_id = :post_id
    ORDER BY comments.created_at ASC
");
// Fetch followers of the user
$followers_stmt = $pdo->prepare("
    SELECT users.id, users.name, users.profile_image ,users.email
    FROM followers 
    JOIN users ON followers.follower_id = users.id 
    WHERE followers.followed_id = :user_id
");
$followers_stmt->execute(['user_id' => $user_id]);
$followers = $followers_stmt->fetchAll(PDO::FETCH_ASSOC);

$following_stmt = $pdo->prepare("
    SELECT users.id, users.name, users.profile_image, users.email
    FROM followers 
    JOIN users ON followers.followed_id = users.id 
    WHERE followers.follower_id = :user_id
");
$following_stmt->execute(['user_id' => $user_id]);
$following = $following_stmt->fetchAll(PDO::FETCH_ASSOC);

$commented_posts_stmt = $pdo->prepare("
   SELECT DISTINCT posts.*, 
           users.profile_image,
           users.name,
		   users.email,
           COALESCE(like_count.likes, 0) AS likes, 
           COALESCE(comment_count.total_comments, 0) AS comments
    FROM posts
    JOIN comments ON posts.id = comments.post_id
    JOIN users ON posts.user_id = users.id
    LEFT JOIN (SELECT post_id, COUNT(*) AS likes FROM likes GROUP BY post_id) AS like_count 
        ON posts.id = like_count.post_id
    LEFT JOIN (SELECT post_id, COUNT(*) AS total_comments FROM comments GROUP BY post_id) AS comment_count 
        ON posts.id = comment_count.post_id
    WHERE comments.user_id = :user_id
    ORDER BY posts.created_at DESC
");
$commented_posts_stmt->execute(['user_id' => $user_id]);
$commented_posts = $commented_posts_stmt->fetchAll(PDO::FETCH_ASSOC);


$posts_stmt->execute(['user_id' => $user_id]);
$posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
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

.user-profile-container {
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

.user-profile-avatar {
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

.user-profile-avatar img, .user-profile-post-avatar img {
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
		
	  width:800px;
			bottom:0;
			
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
		width:50px;
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
		width:100% !important;
		}
	.user-comments{
	width:100%;
	background-color:#f1f1f1;
	border-bottom-left-radius:10px;
	border-bottom-right-radius:10px;
		 font-size: 14px;
	display:none;
	flex-direction:column;
		
	}
	.comment-box{
	display:flex;
	flex-direction:row;
	width:100%;
	background:#ccc;
	align-items:center;
	padding:8px 12px;
	
	}
	.comments-section{
	background:#fff;
	border-bottom-right-radius:10px;
	border-bottom-left-radius:10px;
	padding:8px 12px
	}
	.comment-text{
	flex:1;
		border-radius:20px;
		outline:none;
		background:#fff;
		padding:8px 12px;
		
	}
	.submit-comment{
	background:#1a73e8;
	padding:8px 12px;
	border-radius:20px;
	font-weight:bold;
	border:none;
	cursor:pointer;
	color:#fff;
		
	}
    /* User Avatar */
    .user-avatar {
        margin-right: 15px; /* Space between the avatar and the content */
		display:flex;
		align-items:center;
		gap:5px;
    }
	.comments-section-user-avatar{
	 margin-right: 15px; /* Space between the avatar and the content */
		display:flex;
		align-items:center;
		gap:5px;
	}
	.comments-section-avatar-circle{
	 width: 50px;
        height: 50px;
        border-radius: 50%; /* Circle shape */
		 background-color: #ccc; /* Background color for the avatar */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff; /* White letter color */
        text-align: center;
	}
		
		.comments-section-avatar-circle img{
		width:50px;
		height:50px;
		border-radius:50%;
		object-fit:cover;
			
		}
		.comment p{
		font-weight:bold;
		
		}

    .avatar-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%; /* Circle shape */
        background-color: #ccc; /* Background color for the avatar */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff; /* White letter color */
        text-align: center;
    }

    /* Post Content */
    .post-content {
        flex: 1;
        padding: 5px;
    }

    /* Post Image */
    .user-post-img,.comment-image, #answers-comment-image {
        width: 100%;
        max-width: 100%;
        height: auto;
        margin-top: 10px;
		max-height:500px;
		object-fit:contain;
		background-color:#000;
		background-size:contain;
		
    }

    /* Timestamp (Posted on) */
    .post-content small {
        color: #777;
        font-size: 12px;
        display: block;
        margin-top: 10px;
    }
	.likes-comments-box{
	display:flex;
	flex-direction:row;
	
	}
	.likes-comments{
	border:none;
	display:flex;
	gap:5px;
	
	}
	.like-btn,.comment-btn{
	border:none;
	background:none;
	align-items:center;
	display:flex;
	font-weight:bold;
		font-size:16px;
		color:#000;
	}
	.user-post-container{
	width:100%;
	}
		.user-profile-post-container{
			width:100%;
		}
		.user-profile-container img{
		
		}
	
	@media(max-width:768px){
		.main-content{
		position:absolute;
			left:0;
			max-width:100%;
		}
		.user-post-img, .comment-image{
		max-height:300px;
		}
		
	}
	
	@media (min-width: 820px) and (max-width: 1080px) {
    .main-content {
        max-width: calc(100% - 250px);
    }
}
		
		@media (max-width: 768px) {
  
		
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 28px;
		
		
    }
			.user-profile-content{
			max-width:100%;
				width:100%;
			}

    
		}
		.follow-button,.unfollow-button,.disabled-button{
		padding:8px 12px;
		border-radius:20px;
		background-color:#2c2f6b;
		color:#ffffff;
		font-weight:bold;
		border:3px solid transparent;
		}


	</style>
</head>
<body>
<div class="content">
        <div class="main-content">
            <div class="user-profile-container">
              <div class="user-profile-avatar">
    <?php 
    $profileImage = !empty($user['profile_image']) ? "uploads/" . htmlspecialchars($user['profile_image']) : null;
    $ProfileAvatarLetter = strtoupper(substr($user['email'], 0, 1)); 
    ?>

    <?php if ($profileImage && file_exists($profileImage)) : ?>
        <img src="<?= $profileImage ?>" alt="Profile Image" class="user-profile-avatar-placeholder">
    <?php else : ?>
        <div class="user-profile-avatar-placeholder"><?= $ProfileAvatarLetter; ?></div>
    <?php endif; ?>
</div>

				<div class='user-details'>
				 <h1><?= htmlspecialchars($user['name']); ?></h1>
					<p ><strong > <?= $follower_count; ?></strong> <span style='font-size:16px;' >тіркелуші</span></p>
					
<form action="follow.php" method="POST">
    <input type="hidden" name="followed_id" value="<?= $user_id; ?>">
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id): ?>
        <?php if ($isFollowing): ?>
            <button type="submit" name="action" value="unfollow" class="unfollow-button">Жазылмау</button>
        <?php else: ?>
            <button type="submit" name="action" value="follow" class="follow-button">Жазылу</button>
        <?php endif; ?>
    <?php else: ?>
        <button type="button" class="disabled-button" disabled>Жазылу</button>
    <?php endif; ?>
</form>



              
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
				<div class='user-profile-post-container'>
                <!-- User Avatar: Circle-shaped, use the first letter of the user's email -->
				<div class='user-profile-post-header'>
				   <div class="user-profile-post-avatar">
					  <?php 
    $profileImage = !empty($user['profile_image']) ? "uploads/" . htmlspecialchars($user['profile_image']) : null;
    $ProfileAvatarLetter = strtoupper(substr($user['email'], 0, 1)); 
    ?>

    <?php if ($profileImage && file_exists($profileImage)) : ?>
        <img src="<?= $profileImage ?>" alt="Profile Image" class="user-profile-avatar-placeholder">
    <?php else : ?>
        <div class="user-profile-avatar-placeholder"><?= $ProfileAvatarLetter; ?></div>
    <?php endif; ?>
					   
                </div>
				  <p class='user-profile-user-name'><strong><?php echo htmlspecialchars($user['name']); ?></strong> </p>
					<p>
                      <small>
    <?php 
    $postDate = strtotime($post['created_at']);
    $currentYear = date("Y");
    $postYear = date("Y", $postDate);
    
    echo ($postYear == $currentYear) ? date("M j", $postDate) : date("M j, Y", $postDate);
    ?>
</small></p>
				</div>
            
         
                <!-- Post Content -->
                <div class="user-profile-user-content">
                   
                    <p class='user-profile-text'><?= nl2br(htmlspecialchars($post['content'])); ?></p>
                   
                </div>
				 <!-- Display Post Image if Available -->
                    <?php if ($post['image']): ?>
                        <img src="https://qur.kz/k/<?php echo htmlspecialchars($post['image']); ?>" class='user-post-img' alt="Post Image" style="max-width: 100%; ">
                    <?php endif; ?>
                    
                    
					
								<div class='likes-comments-box'>

		<div class="likes-comments">

 <?php
    // Check if the user has liked the post
    $liked_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
    $liked_stmt->execute([$post['id'], $user_id]);
    $user_liked = $liked_stmt->fetchColumn() > 0;
    ?>
     <button class="like-btn"  data-post-id="<?php echo htmlspecialchars($post['id']); ?>" >
		<svg xmlns="http://www.w3.org/2000/svg"  fill="<?= isset($user_id) ? ($user_liked ? 'red' : 'none') : 'none' ?>"
			 class="like-icon"
			 height="24" 
			 viewBox="0 0 24 24"
			 width="24">
			<path d="m7 3c-2.76142 0-5 2.21619-5 4.95 0 2.207.87466 7.4447 9.4875 12.7403.3119.1918.7131.1918 1.025 0 8.6128-5.2956 9.4875-10.5333 9.4875-12.7403 0-2.73381-2.2386-4.95-5-4.95s-5 3-5 3-2.23858-3-5-3z" 
				fill="<?= $user_liked ? 'red' : 'none' ?>"
              stroke="<?= $user_liked ? 'none' : '#000' ?>"
				  stroke-linecap="round"
				  stroke-linejoin="round"
				  stroke-width='2'
				  /></svg>
		 <span class='like-count'><?= $post['likes'] ?></span>

		

			</button>
  
    <button class="comment-btn" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" height="24" viewBox="0 0 24 24" width="24"  stroke="#000" stroke-width='1' stroke-linejoin="round" id='' stroke-linecap="round"><path d="m4 19-.44721-.2236c-.08733.1746-.06499.3841.05719.5365.12218.1523.32185.2195.51129.1722zm15.5-7.5c0 3.866-3.134 7-7 7v1c4.4183 0 8-3.5817 8-8zm-14 0c0-3.86599 3.13401-7 7-7v-1c-4.41828 0-8 3.58172-8 8zm7-7c3.866 0 7 3.13401 7 7h1c0-4.41828-3.5817-8-8-8zm0 14c-.5821 0-1.252-.2167-1.9692-.4712-.3437-.122-.70179-.2533-1.0332-.3518-.32863-.0977-.67406-.177-.9976-.177v1c.17646 0 .41058.0457.71262.1355.29927.089.62301.2077.98378.3357.692.2455 1.522.5288 2.3036.5288zm-4-1c-.11255 0-.27171.0241-.42258.0504-.16556.0288-.3693.0692-.59449.1166-.45104.0949-1.00237.221-1.53495.3463-.53321.1255-1.0504.2508-1.4341.3448-.19191.047-.35056.0862-.46129.1136-.05536.0137-.09875.0245-.12834.0319-.01479.0037-.02614.0065-.0338.0084-.00383.001-.00674.0017-.0087.0022-.00099.0002-.00173.0004-.00223.0005-.00025.0001-.00045.0001-.00058.0002-.00006 0-.00012 0-.00015 0-.00004 0-.00006 0 .12121.4851s.12128.4851.1213.4851c.00003 0 .00007-.0001.00012-.0001.00011 0 .00029 0 .00052-.0001.00047-.0001.00117-.0003.00212-.0005.00188-.0005.00472-.0012.00847-.0021.0075-.0019.01868-.0047.03331-.0083.02925-.0073.07228-.018.12727-.0316.10997-.0273.26773-.0662.45864-.113.38192-.0935.89598-.2182 1.42527-.3427.52992-.1247 1.07234-.2486 1.51192-.3412.22013-.0463.41092-.084.55982-.1099.07454-.013.13537-.0224.18229-.0285.0515-.0066.07071-.0071.06895-.0071zm-4.5 1.5c.44721.2236.44723.2236.44726.2235.00002 0 .00005-.0001.00008-.0001.00007-.0002.00015-.0004.00026-.0006.00023-.0004.00054-.001.00094-.0018.0008-.0016.00195-.004.00345-.007.00301-.006.00738-.0148.01305-.0263.01133-.0229.02781-.0564.0487-.0991.04177-.0856.10123-.2085.1725-.3589.14238-.3006.33265-.7128.52333-1.1577.19013-.4437.38359-.9266.5304-1.367.14069-.4221.26003-.8658.26003-1.205h-1c0 .1608-.06816.4671-.20872.8888-.13444.4033-.31598.8579-.50085 1.2892-.18432.4301-.36905.8304-.50792 1.1236-.06936.1464-.12708.2657-.16734.3481-.02013.0412-.03588.0732-.04652.0947-.00532.0108-.00936.0189-.01204.0243-.00134.0027-.00233.0047-.00297.006-.00032.0006-.00056.0011-.0007.0014-.00007.0001-.00012.0002-.00014.0003-.00002 0-.00002 0-.00003 0 .00001 0 .00002 0 .44723.2236zm2-4c0-.5586-.13724-1.2669-.25926-1.8921-.12961-.664-.24074-1.2302-.24074-1.6079h-1c0 .4989.13887 1.1827.25926 1.7995.12798.6557.24074 1.2591.24074 1.7005z" fill="#09090b"  /></svg> 
		  <span class='comment-count'><?= $post['comments'] ?></span>
		
			</button>
    
</div>

<!-- Comment Input -->

</div>
            </div>
				<div class='user-comments'>
						<div class="comment-box"  >
    <textarea class="comment-text" placeholder="Пікір қалдырыңыз......"></textarea>
	<label for="comment-image" class="upload-btn">
 <svg fill="#696969" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 45.964 45.964" xml:space="preserve" stroke="#696969"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M7.071,30.834V11.062H4.042C1.803,11.062,0,12.893,0,15.13v26.732c0,2.24,1.803,4.051,4.042,4.051h26.733 c2.238,0,4.076-1.811,4.076-4.051v-2.92H15.179C10.733,38.943,7.071,35.281,7.071,30.834z"></path> <path d="M41.913,0.05H15.179c-2.238,0-4.066,1.813-4.066,4.051v26.733c0,2.241,1.829,4.067,4.066,4.067h26.734 c2.237,0,4.051-1.826,4.051-4.067V4.102C45.964,1.862,44.15,0.05,41.913,0.05z M41.363,28.589 c-0.223,0.412-0.652,0.656-1.12,0.656H17.336c-0.403,0-0.782-0.18-1.022-0.502c-0.24-0.324-0.313-0.736-0.197-1.123l3.277-10.839 c0.216-0.713,0.818-1.24,1.554-1.361c0.736-0.12,1.476,0.19,1.908,0.797l4.582,6.437c0.617,0.867,1.812,1.082,2.689,0.484 l4.219-2.865c0.434-0.295,0.967-0.402,1.48-0.299c0.515,0.102,0.966,0.408,1.253,0.848l4.229,6.472 C41.564,27.687,41.585,28.179,41.363,28.589z"></path> </g> </g> </g></svg>
</label>
<input type="file" id="comment-image" class="comment-image" accept="image/*" hidden>

    <button class="submit-comment" data-post-id="<?= $post['id'] ?>">Пікір қалдыру</button>
		
</div>
					
		    <!-- Comments Section -->
        <div class="comments-section" id="comments-<?= $post['id'] ?>">
            <?php
            // Fetch comments for this post
            $comments_stmt->execute(['post_id' => $post['id']]);
            $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($comments):
                foreach ($comments as $comment):
            ?>
                    <div class="comment">
                         <div class="comments-section-user-avatar">
                    <?php
                    // Get the first letter of the user's email for the avatar
                   $commentAvatarLetter = strtoupper(substr($comment['email'], 0, 1));
				 $profileImage = !empty($comment['profile_image']) ? "uploads/" . htmlspecialchars($comment['profile_image']) : null;
                    ?>
                    <div class="comments-section-avatar-circle">
                        <?php if ($profileImage && file_exists($profileImage)) : ?>
        <img src="<?= $profileImage ?>" alt="Profile Image" class="user-profile-avatar-placeholder">
    <?php else : ?>
        <div class="user-profile-avatar-placeholder"><?= $ProfileAvatarLetter; ?></div>
    <?php endif; ?>
                    </div>
							 
						
							 
							 
							 
							 
							 
					 <p class='comments-section-user-name'><strong><?php echo htmlspecialchars($comment['name']); ?></strong> </p>
					
                      <small>
    <?php 
    $postDate = strtotime($comment['created_at']);
    $currentYear = date("Y");
    $postYear = date("Y", $postDate);
    
    echo ($postYear == $currentYear) ? date("M j", $postDate) : date("M j, Y", $postDate);
    ?>
</small>
                </div>
                      <p><?= nl2br(htmlspecialchars_decode($comment['user_comment']), ENT_QUOTES) ?></p>

						 <?php if (!empty($comment['comment_image'])): ?>
    <img src="https://qur.kz/uploads/<?= htmlspecialchars($comment['comment_image']) ?>" alt="Comment Image" class="comment-image">
<?php endif; ?>


                    </div>
            <?php 
                endforeach;
            else: 
            ?>
                <p></p>
            <?php endif; ?>
        </div>
				</div>



		
				
						    </div>
        <?php endforeach; ?>
    </div>
			 <div id="followers" class="profile-section" style='display:none'>
    <?php if (!empty($followers)): ?>
        <div class="followers-list">
            <?php foreach ($followers as $follower): ?>
                <div class="follower-item">
					 <?php 
    $profileImage = !empty($follower['profile_image']) ? "uploads/" . htmlspecialchars($follower['profile_image']) : null;
    $ProfileAvatarLetter = strtoupper(substr($follower['email'], 0, 1)); 
    ?>

    <?php if ($profileImage && file_exists($profileImage)) : ?>
        <img src="<?= $profileImage ?>" alt="Profile Image" class="user-profile-avatar-placeholder">
    <?php else : ?>
        <div class="follower-placeholder"><?= $ProfileAvatarLetter; ?></div>
    <?php endif; ?>
					<a href="https://qur.kz/k/profile.php?user_id=<?= htmlspecialchars($follower['id']) ?>">
    <span><?= htmlspecialchars($follower['name']) ?></span>
</a>

                   
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        
    <?php endif; ?>
</div>
		   
	<div id="following" class="profile-section" style='display:none'>
    <?php if (!empty($following)): ?>
        <div class="followers-list">
            <?php foreach ($following as $followedUser): ?>
                <div class="follower-item">
                    <?php 
                    $profileImage = !empty($followedUser['profile_image']) ? "uploads/" . htmlspecialchars($followedUser['profile_image']) : null;
                    $ProfileAvatarLetter = strtoupper(substr($followedUser['email'], 0, 1)); 
                    ?>

                    <?php if ($profileImage && file_exists($profileImage)) : ?>
                        <img src="<?= $profileImage ?>" alt="Profile Image" class="user-profile-avatar-placeholder">
                    <?php else : ?>
                        <div class="follower-placeholder"><?= $ProfileAvatarLetter; ?></div>
                    <?php endif; ?>

                    <a href="https://qur.kz/k/profile.php?user_id=<?= htmlspecialchars($followedUser['id']) ?>">
                        <span><?= htmlspecialchars($followedUser['name']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
       
    <?php endif; ?>
</div>
				
<div id="answers" class="profile-section" style="display: none;">
    <?php foreach ($commented_posts as $post): ?>
     <div   class="user-profile-post">
				<div class='user-profile-post-container'>
                <!-- User Avatar: Circle-shaped, use the first letter of the user's email -->
				<div class='user-profile-post-header'>
				   <div class="user-profile-post-avatar">
					  <?php 
    $profileImage = !empty($post['profile_image']) ? "uploads/" . htmlspecialchars($post['profile_image']) : null;
    $ProfileAvatarLetter = strtoupper(substr($post['email'], 0, 1)); 
    ?>

    <?php if ($profileImage && file_exists($profileImage)) : ?>
        <img src="<?= $profileImage ?>" alt="Profile Image" class="user-profile-avatar-placeholder">
    <?php else : ?>
        <div class="user-profile-avatar-placeholder"><?= $ProfileAvatarLetter; ?></div>
    <?php endif; ?>
					   
                </div>
				  <p class='user-profile-user-name'><strong><?php echo htmlspecialchars($post['name']); ?></strong> </p>
					<p>
                      <small>
    <?php 
    $postDate = strtotime($post['created_at']);
    $currentYear = date("Y");
    $postYear = date("Y", $postDate);
    
    echo ($postYear == $currentYear) ? date("M j", $postDate) : date("M j, Y", $postDate);
    ?>
</small></p>
				</div>
            
         
                <!-- Post Content -->
                <div class="user-profile-user-content">
                   
                    <p class='user-profile-text'><?= nl2br(htmlspecialchars($post['content'])); ?></p>
                   
                </div>
				 <!-- Display Post Image if Available -->
                    <?php if ($post['image']): ?>
                        <img src="https://qur.kz/k/<?php echo htmlspecialchars($post['image']); ?>" class='user-post-img' alt="Post Image" style="max-width: 100%; ">
                    <?php endif; ?>
                    
                    
					
								<div class='likes-comments-box'>

		<div class="likes-comments">

 <?php
    // Check if the user has liked the post
    $liked_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
    $liked_stmt->execute([$post['id'], $user_id]);
    $user_liked = $liked_stmt->fetchColumn() > 0;
    ?>
     <button class="like-btn"  data-post-id="<?php echo htmlspecialchars($post['id']); ?>" >
		<svg xmlns="http://www.w3.org/2000/svg"  fill="<?= isset($user_id) ? ($user_liked ? 'red' : 'none') : 'none' ?>"
			 class="like-icon"
			 height="24" 
			 viewBox="0 0 24 24"
			 width="24">
			<path d="m7 3c-2.76142 0-5 2.21619-5 4.95 0 2.207.87466 7.4447 9.4875 12.7403.3119.1918.7131.1918 1.025 0 8.6128-5.2956 9.4875-10.5333 9.4875-12.7403 0-2.73381-2.2386-4.95-5-4.95s-5 3-5 3-2.23858-3-5-3z" 
				fill="<?= $user_liked ? 'red' : 'none' ?>"
              stroke="<?= $user_liked ? 'none' : '#000' ?>"
				  stroke-linecap="round"
				  stroke-linejoin="round"
				  stroke-width='2'
				  /></svg>
		 <span class='like-count'><?= $post['likes'] ?></span>

		

			</button>
  
    <button class="comment-btn" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" height="24" viewBox="0 0 24 24" width="24"  stroke="#000" stroke-width='1' stroke-linejoin="round" id='' stroke-linecap="round"><path d="m4 19-.44721-.2236c-.08733.1746-.06499.3841.05719.5365.12218.1523.32185.2195.51129.1722zm15.5-7.5c0 3.866-3.134 7-7 7v1c4.4183 0 8-3.5817 8-8zm-14 0c0-3.86599 3.13401-7 7-7v-1c-4.41828 0-8 3.58172-8 8zm7-7c3.866 0 7 3.13401 7 7h1c0-4.41828-3.5817-8-8-8zm0 14c-.5821 0-1.252-.2167-1.9692-.4712-.3437-.122-.70179-.2533-1.0332-.3518-.32863-.0977-.67406-.177-.9976-.177v1c.17646 0 .41058.0457.71262.1355.29927.089.62301.2077.98378.3357.692.2455 1.522.5288 2.3036.5288zm-4-1c-.11255 0-.27171.0241-.42258.0504-.16556.0288-.3693.0692-.59449.1166-.45104.0949-1.00237.221-1.53495.3463-.53321.1255-1.0504.2508-1.4341.3448-.19191.047-.35056.0862-.46129.1136-.05536.0137-.09875.0245-.12834.0319-.01479.0037-.02614.0065-.0338.0084-.00383.001-.00674.0017-.0087.0022-.00099.0002-.00173.0004-.00223.0005-.00025.0001-.00045.0001-.00058.0002-.00006 0-.00012 0-.00015 0-.00004 0-.00006 0 .12121.4851s.12128.4851.1213.4851c.00003 0 .00007-.0001.00012-.0001.00011 0 .00029 0 .00052-.0001.00047-.0001.00117-.0003.00212-.0005.00188-.0005.00472-.0012.00847-.0021.0075-.0019.01868-.0047.03331-.0083.02925-.0073.07228-.018.12727-.0316.10997-.0273.26773-.0662.45864-.113.38192-.0935.89598-.2182 1.42527-.3427.52992-.1247 1.07234-.2486 1.51192-.3412.22013-.0463.41092-.084.55982-.1099.07454-.013.13537-.0224.18229-.0285.0515-.0066.07071-.0071.06895-.0071zm-4.5 1.5c.44721.2236.44723.2236.44726.2235.00002 0 .00005-.0001.00008-.0001.00007-.0002.00015-.0004.00026-.0006.00023-.0004.00054-.001.00094-.0018.0008-.0016.00195-.004.00345-.007.00301-.006.00738-.0148.01305-.0263.01133-.0229.02781-.0564.0487-.0991.04177-.0856.10123-.2085.1725-.3589.14238-.3006.33265-.7128.52333-1.1577.19013-.4437.38359-.9266.5304-1.367.14069-.4221.26003-.8658.26003-1.205h-1c0 .1608-.06816.4671-.20872.8888-.13444.4033-.31598.8579-.50085 1.2892-.18432.4301-.36905.8304-.50792 1.1236-.06936.1464-.12708.2657-.16734.3481-.02013.0412-.03588.0732-.04652.0947-.00532.0108-.00936.0189-.01204.0243-.00134.0027-.00233.0047-.00297.006-.00032.0006-.00056.0011-.0007.0014-.00007.0001-.00012.0002-.00014.0003-.00002 0-.00002 0-.00003 0 .00001 0 .00002 0 .44723.2236zm2-4c0-.5586-.13724-1.2669-.25926-1.8921-.12961-.664-.24074-1.2302-.24074-1.6079h-1c0 .4989.13887 1.1827.25926 1.7995.12798.6557.24074 1.2591.24074 1.7005z" fill="#09090b"  /></svg> 
		  <span class='comment-count'><?= $post['comments'] ?></span>
		
			</button>
    
</div>

<!-- Comment Input -->

</div>
            </div>
				<div class='user-comments'>
						<div class="comment-box"  >
    <textarea class="comment-text" placeholder="Пікір қалдырыңыз......"></textarea>
	<label for="comment-image" class="upload-btn">
 <svg fill="#696969" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 45.964 45.964" xml:space="preserve" stroke="#696969"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M7.071,30.834V11.062H4.042C1.803,11.062,0,12.893,0,15.13v26.732c0,2.24,1.803,4.051,4.042,4.051h26.733 c2.238,0,4.076-1.811,4.076-4.051v-2.92H15.179C10.733,38.943,7.071,35.281,7.071,30.834z"></path> <path d="M41.913,0.05H15.179c-2.238,0-4.066,1.813-4.066,4.051v26.733c0,2.241,1.829,4.067,4.066,4.067h26.734 c2.237,0,4.051-1.826,4.051-4.067V4.102C45.964,1.862,44.15,0.05,41.913,0.05z M41.363,28.589 c-0.223,0.412-0.652,0.656-1.12,0.656H17.336c-0.403,0-0.782-0.18-1.022-0.502c-0.24-0.324-0.313-0.736-0.197-1.123l3.277-10.839 c0.216-0.713,0.818-1.24,1.554-1.361c0.736-0.12,1.476,0.19,1.908,0.797l4.582,6.437c0.617,0.867,1.812,1.082,2.689,0.484 l4.219-2.865c0.434-0.295,0.967-0.402,1.48-0.299c0.515,0.102,0.966,0.408,1.253,0.848l4.229,6.472 C41.564,27.687,41.585,28.179,41.363,28.589z"></path> </g> </g> </g></svg>
</label>
<input type="file" id="comment-image" class="comment-image" accept="image/*" hidden>

    <button class="submit-comment" data-post-id="<?= $post['id'] ?>">Пікір қалдыру</button>
		
</div>
					
		    <!-- Comments Section -->
        <div class="comments-section" id="comments-<?= $post['id'] ?>">
            <?php
            // Fetch comments for this post
            $comments_stmt->execute(['post_id' => $post['id']]);
            $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($comments):
                foreach ($comments as $comment):
            ?>
                    <div class="comment">
                         <div class="comments-section-user-avatar">
                    <?php
                    // Get the first letter of the user's email for the avatar
                   $commentAvatarLetter = strtoupper(substr($comment['email'], 0, 1));
				 $profileImage = !empty($comment['profile_image']) ? "uploads/" . htmlspecialchars($comment['profile_image']) : null;
                    ?>
							 
							 
                    <div class="comments-section-avatar-circle">
                       <?php if ($profileImage && file_exists($profileImage)) : ?>
        <img src="<?= $profileImage ?>" alt="Profile Image" class="user-profile-avatar-placeholder">
    <?php else : ?>
        <div class="user-profile-avatar-placeholder"><?= $ProfileAvatarLetter; ?></div>
    <?php endif; ?>
                    </div>
					 <p class='comments-section-user-name'><strong><?php echo htmlspecialchars($comment['name']); ?></strong> </p>
					
                      <small>
    <?php 
    $postDate = strtotime($comment['created_at']);
    $currentYear = date("Y");
    $postYear = date("Y", $postDate);
    
    echo ($postYear == $currentYear) ? date("M j", $postDate) : date("M j, Y", $postDate);
    ?>
</small>
                </div>
                      <p><?= nl2br(htmlspecialchars_decode($comment['user_comment']), ENT_QUOTES) ?></p>

						 <?php if (!empty($comment['comment_image'])): ?>
    <img src="https://qur.kz/uploads/<?= htmlspecialchars($comment['comment_image']) ?>" alt="Comment Image" class="comment-image" id='answers-comment-image'>
<?php endif; ?>


                    </div>
            <?php 
                endforeach;
            else: 
            ?>
                <p></p>
            <?php endif; ?>
        </div>
				</div>



		
				
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
        document.getElementById(sectionId).style.display = 'flex';
    }
</script>
	<script>



document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', function () {
        let postId = this.dataset.postId;
        let likeCountElem = this.querySelector('.like-count');
        let likeIcon = this.querySelector('.like-icon path');

        fetch('https://qur.kz/like_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${postId}&user_id=<?= $user_id ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            likeCountElem.textContent = data.like_count;
            let userLiked = data.liked_by.includes(<?= $user_id ?>);
            likeIcon.setAttribute('fill', userLiked ? 'none' : 'red');
            likeIcon.setAttribute('stroke', userLiked ? '#000' : 'none');
        })
        .catch(err => console.error("Error:", err));
    });
});
</script>
	

<script>

    document.querySelectorAll(".comment-btn").forEach(button => {
        button.addEventListener("click", function () {   // Find the closest `.user-comments` section for this button's post
        const postId = this.getAttribute("data-post-id");
        const commentSection = document.querySelector(`#comments-${postId}`).closest('.user-comments');

        if (commentSection) {
           commentSection.style.display = (commentSection.style.display === "none" || commentSection.style.display === "") ? "flex" : "none";
        }  
        });
    });
document.querySelectorAll(".submit-comment").forEach(button => {
    button.addEventListener("click", function () {
        let postId = this.getAttribute("data-post-id");
        let commentText = this.closest(".comment-box").querySelector(".comment-text"); // Find the textarea inside the comment-box
        let imageInput = this.closest(".comment-box").querySelector(".comment-image"); // Find the file input
        let commentsSection = document.getElementById(`comments-${postId}`);

        if (commentText && commentText.value.trim() !== "" || (imageInput && imageInput.files.length > 0)) {
            let formData = new FormData();
            formData.append("post_id", postId);
            formData.append("user_comment", commentText.value.trim());
            if (imageInput.files.length > 0) {
                formData.append("comment_image", imageInput.files[0]);
            }

            fetch("https://qur.kz/comment_post.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let newComment = document.createElement("div");
                    newComment.innerHTML = `<p><strong>${data.user_name}:</strong> ${data.comment}</p>`;

                    if (data.comment_image) {
                        let img = document.createElement("img");
                        img.src = "https://qur.kz/uploads/comments/" + data.comment_image;
                        img.style.maxWidth = "100px";
                        img.style.maxHeight = "100px";
                        newComment.appendChild(img);
                    }

                    commentsSection.appendChild(newComment);
                    commentText.value = ""; // Clear input field after submission
                    imageInput.value = ""; // Reset file input
                } else {
                    alert(data.error);
                }
            })
            .catch(error => console.error("Error:", error));
        } else {
            alert("Please enter a comment or upload an image.");
        }
    });
});


</script>
	<style>
		#followers, #following{
		display:flex;
		
		}
		.follower-item{
			display:flex;
			flex-direction:row;
			align-items:center;
			border:1px solid #ccc;
			padding:12px 12px;
			gap:10px;
			margin:10px;
			background-color:#fff;
			border-radius:50px;
			
		}
		.follower-item img{
		width:50px;
		height:50px;
		border-radius:50%;
		object-fit:cover;
			
		}
		.followers-list{
		width:100%;
		}
		.follower-placeholder{
		background-color:#ccc;
		display:flex;
		align-items:center;
		justify-content:center;
		width:50px;
		height:50px;
		border-radius:50%;
		color:#fff;
		
		}
		.follower-item a{
		text-decoration:none;
		color:#000;
		font-size:20px;
		font-weight:bold;
		
		}
		.profile-section{
		flex-direction:column;
		padding-bottom:100px;
		}
		
	</style>
</body>
</html>
