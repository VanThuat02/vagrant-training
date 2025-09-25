<?php
// Start the session
session_start();

require_once 'models/UserModel.php';
require_once 'csrf_helper.php';

$userModel = new UserModel();

// Redis
$redis = new Redis();
$redis->connect('training-redis', 6379);

$params = [];
if (!empty($_GET['keyword'])) {
    $params['keyword'] = $_GET['keyword'];
}

// lay danh sach users tu DB
$users = $userModel->getUsers($params);

// thu lay user login tu Redis (neu co)
$loginUser = null;
if (!empty($_SESSION['id'])) {
    $redisKey = 'user:login:' . $_SESSION['id'];
    if ($redis->exists($redisKey)) {
        $loginUser = json_decode($redis->get($redisKey), true);
    }
}

$csrf_token = CSRF_Protection::generateToken();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Home</title>
    <?php include 'views/meta.php' ?>
</head>

<body>
    <?php include 'views/header.php' ?>
    <div class="container">
        <?php if (!empty($users)) { ?>
            <div class="alert alert-warning" role="alert">
                List of users!
            </div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Fullname</th>
                        <th scope="col">Type</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) { ?>
                        <tr>
                            <th scope="row"><?php echo $user['id'] ?></th>
                            <td><?php echo $user['name'] ?></td>
                            <td><?php echo $user['fullname'] ?></td>
                            <td><?php echo $user['type'] ?></td>
                            <td>
                                <a href="form_user.php?id=<?php echo $user['id'] ?>">
                                    <i class="fa fa-pencil-square-o" title="Update"></i>
                                </a>
                                <a href="view_user.php?id=<?php echo $user['id'] ?>">
                                    <i class="fa fa-eye" title="View"></i>
                                </a>

                                <!-- Form xoá dùng POST + CSRF -->
                                <form action="delete_user.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <button type="submit" style="border:none;background:none;">
                                        <i class="fa fa-eraser text-danger" title="Delete"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="alert alert-dark" role="alert">
                No users found!
            </div>
        <?php } ?>
    </div>
</body>

</html>