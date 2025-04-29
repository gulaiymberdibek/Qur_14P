<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
	
<script src="https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.2/dist/index.min.js"></script>
<script type="module" src="https://richinfo.co/richpartners/push/js/rp-cl-ob.js?pubid=961179&siteid=358058&niche=33" async data-cfasync="false"></script>
   
</head>
<body>

<?php
session_start();
require 'header.php';

$db=new DBS();
$pdo=$db->getConnection();
$id = $_GET['post_id'] ?? null;

if (!$id) {
    die("Invalid post ID.");
}

$stmt = $pdo->prepare("
    SELECT posts.*, users.name, users.profile_image, users.email,  
           COALESCE(like_count.likes, 0) AS likes,
           COALESCE(comment_count.total_comments, 0) AS comment_count  
    FROM posts 
    JOIN users ON posts.user_id = users.id  
    LEFT JOIN (
        SELECT post_id, COUNT(*) AS likes 
        FROM likes 
        GROUP BY post_id
    ) AS like_count ON posts.id = like_count.post_id 
    LEFT JOIN (
        SELECT post_id, COUNT(*) AS total_comments 
        FROM comments 
        GROUP BY post_id
    ) AS comment_count ON posts.id = comment_count.post_id  
    WHERE posts.id = ?
    ORDER BY posts.created_at DESC
");

$stmt->execute([$id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$posts) {
    die("Post not found.");
}

$comments_stmt = $pdo->prepare("SELECT comments.*, users.name, users.profile_image, users.email FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = :post_id ORDER BY comments.created_at ASC");

?>

<div class="main-content">

<?php foreach ($posts as $post): ?>

    <div class="post">
        <div class='user-post-container'>
            <div class="user-avatar">
                <?php
                $profileImage = !empty($post['profile_image']) ? "https://qur.kz/k/uploads/" . htmlspecialchars($post['profile_image']) : null;
                $avatarLetter = strtoupper(substr($post['email'], 0, 1));
                ?>

                <a href="https://qur.kz/k/profile.php?user_id=<?= htmlspecialchars($post['user_id']) ?>" style="text-decoration:none">
                    <?php if ($profileImage) : ?>
                        <img src="<?= $profileImage ?>" alt="Profile Image" class="avatar-img">
                    <?php else : ?>
                        <div class="avatar-circle"><?= $avatarLetter; ?></div>
                    <?php endif; ?>
                </a>

                <p class="user-name"><strong><?= htmlspecialchars($post['name']); ?></strong></p>
                <small>
                    <?php 
                    $postDate = strtotime($post['created_at']);
                    $currentYear = date("Y");
                    $postYear = date("Y", $postDate);
                    echo ($postYear == $currentYear) ? date("M j", $postDate) : date("M j, Y", $postDate);
                    ?>
                </small>
            </div>
        </div>

        <div class="post-content">
            <p class='user-text'><?= nl2br(htmlspecialchars($post['content'])); ?></p>
        </div>
     
        <?php if (!empty($post['music'])): ?>
            <audio controls>
                <source src="https://qur.kz/k/<?= htmlspecialchars($post['music']); ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        <?php endif; ?>

        <?php if (!empty($post['image'])): ?>
            <img src="https://qur.kz/k/<?= htmlspecialchars($post['image']); ?>" 
                 class="user-post-img" 
                 alt="Post Image" 
                 style="max-width: 100%; cursor: pointer;" 
                 onclick="showFullScreenImage(this.src)">
        <?php endif; ?>

        <div id="fullImageOverlay" class="overlay" onclick="hideFullScreenImage()">
            <img id="fullImage" src="" >
        </div>

        <div class='likes-comments-box'>
            <div class="likes-comments">
                <?php
                $user_id = $_SESSION['user_id'] ?? 0;
                $liked_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
                $liked_stmt->execute([$post['id'], $user_id]);
                $user_liked = $liked_stmt->fetchColumn() > 0;
                ?>

                <button class="like-btn" data-post-id="<?= htmlspecialchars($post['id']); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg"  
                         fill="<?= $user_liked ? 'red' : 'none' ?>" 
                         class="like-icon"
                         height="24" 
                         viewBox="0 0 24 24"
                         width="24">
                        <path d="m7 3c-2.76142 0-5 2.21619-5 4.95 0 2.207.87466 7.4447 9.4875 12.7403.3119.1918.7131.1918 1.025 0 8.6128-5.2956 9.4875-10.5333 9.4875-12.7403 0-2.73381-2.2386-4.95-5-4.95s-5 3-5 3-2.23858-3-5-3z" 
                             fill="<?= $user_liked ? 'red' : 'none' ?>"
                             stroke="<?= $user_liked ? 'none' : '#000' ?>"
                             stroke-linecap="round"
                             stroke-linejoin="round"
                             stroke-width='2'/>
                    </svg>
                    <span class='like-count'><?= $post['likes'] ?></span>
                </button>
            </div>
			  <button class="comment-btn" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" height="24" viewBox="0 0 24 24" width="24"  stroke="#000" stroke-width='1' stroke-linejoin="round" id='' stroke-linecap="round"><path d="m4 19-.44721-.2236c-.08733.1746-.06499.3841.05719.5365.12218.1523.32185.2195.51129.1722zm15.5-7.5c0 3.866-3.134 7-7 7v1c4.4183 0 8-3.5817 8-8zm-14 0c0-3.86599 3.13401-7 7-7v-1c-4.41828 0-8 3.58172-8 8zm7-7c3.866 0 7 3.13401 7 7h1c0-4.41828-3.5817-8-8-8zm0 14c-.5821 0-1.252-.2167-1.9692-.4712-.3437-.122-.70179-.2533-1.0332-.3518-.32863-.0977-.67406-.177-.9976-.177v1c.17646 0 .41058.0457.71262.1355.29927.089.62301.2077.98378.3357.692.2455 1.522.5288 2.3036.5288zm-4-1c-.11255 0-.27171.0241-.42258.0504-.16556.0288-.3693.0692-.59449.1166-.45104.0949-1.00237.221-1.53495.3463-.53321.1255-1.0504.2508-1.4341.3448-.19191.047-.35056.0862-.46129.1136-.05536.0137-.09875.0245-.12834.0319-.01479.0037-.02614.0065-.0338.0084-.00383.001-.00674.0017-.0087.0022-.00099.0002-.00173.0004-.00223.0005-.00025.0001-.00045.0001-.00058.0002-.00006 0-.00012 0-.00015 0-.00004 0-.00006 0 .12121.4851s.12128.4851.1213.4851c.00003 0 .00007-.0001.00012-.0001.00011 0 .00029 0 .00052-.0001.00047-.0001.00117-.0003.00212-.0005.00188-.0005.00472-.0012.00847-.0021.0075-.0019.01868-.0047.03331-.0083.02925-.0073.07228-.018.12727-.0316.10997-.0273.26773-.0662.45864-.113.38192-.0935.89598-.2182 1.42527-.3427.52992-.1247 1.07234-.2486 1.51192-.3412.22013-.0463.41092-.084.55982-.1099.07454-.013.13537-.0224.18229-.0285.0515-.0066.07071-.0071.06895-.0071zm-4.5 1.5c.44721.2236.44723.2236.44726.2235.00002 0 .00005-.0001.00008-.0001.00007-.0002.00015-.0004.00026-.0006.00023-.0004.00054-.001.00094-.0018.0008-.0016.00195-.004.00345-.007.00301-.006.00738-.0148.01305-.0263.01133-.0229.02781-.0564.0487-.0991.04177-.0856.10123-.2085.1725-.3589.14238-.3006.33265-.7128.52333-1.1577.19013-.4437.38359-.9266.5304-1.367.14069-.4221.26003-.8658.26003-1.205h-1c0 .1608-.06816.4671-.20872.8888-.13444.4033-.31598.8579-.50085 1.2892-.18432.4301-.36905.8304-.50792 1.1236-.06936.1464-.12708.2657-.16734.3481-.02013.0412-.03588.0732-.04652.0947-.00532.0108-.00936.0189-.01204.0243-.00134.0027-.00233.0047-.00297.006-.00032.0006-.00056.0011-.0007.0014-.00007.0001-.00012.0002-.00014.0003-.00002 0-.00002 0-.00003 0 .00001 0 .00002 0 .44723.2236zm2-4c0-.5586-.13724-1.2669-.25926-1.8921-.12961-.664-.24074-1.2302-.24074-1.6079h-1c0 .4989.13887 1.1827.25926 1.7995.12798.6557.24074 1.2591.24074 1.7005z" fill="#09090b"  /></svg> 
		 <span class="comment-count"><?= $post['comment_count'] ?? 0 ?></span>
		
			</button>
    
        </div>
    </div>

	
		<div class='user-comments'>
		<div class="comment-box"  >
    <textarea class="comment-text" placeholder="Пікір қалдырыңыз......"></textarea>
	<label for="comment-image-<?php echo htmlspecialchars($post['id']); ?>" class="upload-btn">
 <svg fill="#696969" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 45.964 45.964" xml:space="preserve" stroke="#696969"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M7.071,30.834V11.062H4.042C1.803,11.062,0,12.893,0,15.13v26.732c0,2.24,1.803,4.051,4.042,4.051h26.733 c2.238,0,4.076-1.811,4.076-4.051v-2.92H15.179C10.733,38.943,7.071,35.281,7.071,30.834z"></path> <path d="M41.913,0.05H15.179c-2.238,0-4.066,1.813-4.066,4.051v26.733c0,2.241,1.829,4.067,4.066,4.067h26.734 c2.237,0,4.051-1.826,4.051-4.067V4.102C45.964,1.862,44.15,0.05,41.913,0.05z M41.363,28.589 c-0.223,0.412-0.652,0.656-1.12,0.656H17.336c-0.403,0-0.782-0.18-1.022-0.502c-0.24-0.324-0.313-0.736-0.197-1.123l3.277-10.839 c0.216-0.713,0.818-1.24,1.554-1.361c0.736-0.12,1.476,0.19,1.908,0.797l4.582,6.437c0.617,0.867,1.812,1.082,2.689,0.484 l4.219-2.865c0.434-0.295,0.967-0.402,1.48-0.299c0.515,0.102,0.966,0.408,1.253,0.848l4.229,6.472 C41.564,27.687,41.585,28.179,41.363,28.589z"></path> </g> </g> </g></svg>
</label>
<input type="file" id="comment-image-<?php echo htmlspecialchars($post['id']); ?>" class="comment-image" accept="image/*" hidden>

    <button class="submit-comment" data-post-id="<?= $post['id'] ?>">Пікір қалдыру</button>
		
</div>
		
    <div class="comments-section" id="comments-<?= $post['id'] ?>">
        <?php
        $comments_stmt->execute(['post_id' => $post['id']]);
        $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($comments):
            foreach ($comments as $comment):
        ?>
                <div class="comment">
                    <div class="comments-section-user-avatar">
                        <?php
                        $avatarLetter = strtoupper(substr($comment['email'], 0, 1));
                        $profileImage = !empty($comment['profile_image']) ? "https://qur.kz/k/uploads/" . htmlspecialchars($comment['profile_image']) : null;
                        ?>
                        <div class="comments-section-avatar-circle">
                            <?php if ($profileImage) : ?>
                                <img src="<?= $profileImage ?>" alt="Profile Image" class="avatar-img">
                            <?php else : ?>
                                <div class="avatar-circle">
                                    <?= $avatarLetter; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class='comments-section-user-name'><strong><?= htmlspecialchars($comment['name']); ?></strong></p>
                    </div>
                    <p><?= nl2br(htmlspecialchars_decode($comment['user_comment']), ENT_QUOTES) ?></p>
										<?php if (!empty($comment['comment_image'])): ?>
    <img src="uploads/<?= htmlspecialchars($comment['comment_image']) ?>" 
         alt="Comment Image" 
         class="comment-image" 
         style="max-width: 100%; cursor: pointer;" 
         onclick="showFullScreenImage(this.src)">
<?php endif; ?>

<!-- Fullscreen Image Overlay -->
<div id="fullImageOverlay" class="overlay" onclick="hideFullScreenImage()">
    <img id="fullImage" src="" >
</div>

                </div>
        <?php endforeach; endif; ?>
    </div>
		</div>
	
	
	
<?php endforeach; ?>

</div>

</body>
</html>
<style>
   
	 .main-content{
      display: flex;
      max-width: calc(100% - 250px);
    flex-direction: column;
    height:100%;
    position:absolute;
	top:70px;
	left:250px;
	width:100%;
	 border-right: 2px solid #ccc; /* Right border */
    overflow-y:block;
	background-color:#fff;
   
    }
	
	
	/* Individual post */
    .post {
        display: flex;
		flex-direction:column;
        align-items: flex-start;
        margin-bottom: 20px;
       
        background-color: #fff;
        border-radius: 10px;
       margin:10px;
    }
	.user-post-container{
	padding:20px;
	}
	.user-comments{
	width:100%;
	background-color:#f1f1f1;
	border-bottom-left-radius:10px;
	border-bottom-right-radius:10px;
		 font-size: 14px;
	
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
	padding:8px 12px;
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

    .avatar-circle, .avatar-img {
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
		object-fit:cover;
    }

    /* Post Content */
    .post-content {
        flex: 1;
        padding: 5px;
    }

    /* Post Image */
    .user-post-img,.comment-image {
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
	
 
</style>

<script>
document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', function() {
        let icon = this.querySelector('.like-icon path');
        let isLiked = icon.getAttribute('fill') === 'red';

        icon.setAttribute('fill', isLiked ? 'none' : 'red');
        icon.setAttribute('stroke', isLiked ? '#000' : 'none'); // Toggle stroke
    });
});
document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', function() {
        let postId = this.getAttribute('data-post-id');
        let likeCount = this.querySelector('.like-count');
        let likeIcon = this.querySelector('.like-icon path');
        let likedUsersList = this.closest('.post').querySelector('.liked-users');
  
        fetch('like_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'post_id=' + postId
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            
            likeCount.textContent = data.like_count;
            likedUsersList.textContent = data.liked_by.join(', ') || "No likes yet";
            likeIcon.setAttribute('fill', data.liked_by.includes("<?= $_SESSION['name'] ?? '' ?>") ? 'red' : 'none');
            likeIcon.setAttribute('stroke', data.liked_by.includes("<?= $_SESSION['name'] ?? '' ?>") ? 'none' : '#000');
        });
    });
});

</script>
<style>
	.main-content-users-post{}

.comment-btn:hover {
    color: #ccc;
	}

.comment-btn svg {
    width: 20px;
    height: 20px;
    stroke: #333;
    transition: 0.3s;
}



/* Comment Box */
.comment-box {
    display:flex;
    flex-direction: row;
   
}

.comment-text {
  
}

.comment-text:focus {
  
}

/* Submit Comment Button */
.submit-comment {
    
}



/* Comments Section */
.comments-section {
   
}



.comment {
	border-bottom:1px solid #ccc;
	margin-bottom:5px;
   
}

.comment strong {
 
}

.comment time {
    font-size: 12px;
   
    margin-top: 4px;
}
	.upload-btn {
  
    padding: 10px 20px;
  
}

.upload-btn:hover {
    background-color: #000; /* Darker shade on hover */
}
	.comment p{
	padding:20px;
	font-size:20px;
	}

	
</style>
<script>

    document.querySelectorAll(".comment-btn").forEach(button => {
        button.addEventListener("click", function () {   // Find the closest `.user-comments` section for this button's post
        const postId = this.getAttribute("data-post-id");
        const commentSection = document.querySelector(`#comments-${postId}`).closest('.user-comments');

        
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

            fetch("comment_post.php", {
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
                        img.src = "uploads/comments/" + data.comment_image;
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
/* Fullscreen overlay */
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.overlay img {
    max-width: 90%;
    max-height: 90%;
}
</style>
<script>
function showFullScreenImage(src) {
    document.getElementById("fullImage").src = src;
    document.getElementById("fullImageOverlay").style.display = "flex";
}

function hideFullScreenImage() {
    document.getElementById("fullImageOverlay").style.display = "none";
}