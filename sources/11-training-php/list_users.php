<?php
require_once 'session_security.php'; // <-- important: must be first
require_once 'models/UserModel.php';
$userModel = new UserModel();

// Redis (existing)
$redis = new Redis();
$redis->connect('training-redis', 6379);

// collect params safely (still pass raw to model; assume model uses prepared statements)
$params = [];
if (!empty($_GET['keyword'])) {
    $params['keyword'] = $_GET['keyword'];
}

// lay danh sach users tu DB (UserModel must use prepared statements)
$users = $userModel->getUsers($params);

// thu lay user login tu Redis (neu co) - prefer Redis but fallback to DB
$loginUser = null;
if (!empty($_SESSION['id'])) {
    $redisKey = 'user:login:' . intval($_SESSION['id']);
    if ($redis->exists($redisKey)) {
        $loginUser = json_decode($redis->get($redisKey), true);
    } else {
        $user = $userModel->findUserById(intval($_SESSION['id']));
        if (!empty($user)) {
            $loginUser = $user[0];
            $redis->set($redisKey, json_encode($loginUser));
            $redis->expire($redisKey, 300);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Home</title>
    <?php include 'views/meta.php' ?>
    <style>
        .username-display {
            position: absolute;
            top: 10px;
            right: 10px;
            font-weight: bold;
            color: #000;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <?php include 'views/header.php' ?>

    <div class="container">
        <?php if (!empty($loginUser)) { ?>
            <div class="username-display">
                Welcome, <?= esc($loginUser['name']) ?>!
            </div>
        <?php } else { ?>
            <div class="username-display">
                Not logged in
            </div>
        <?php } ?>

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
                            <th scope="row"><?= intval($user['id']) ?></th>
                            <td><?= esc($user['name']) ?></td>
                            <td><?= esc($user['fullname']) ?></td>
                            <td><?= esc($user['type']) ?></td>
                            <td>
                                <a href="form_user.php?id=<?= intval($user['id']) ?>" title="Update">
                                    <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                                </a>
                                <a href="view_user.php?id=<?= intval($user['id']) ?>" title="View">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                                <a href="delete_user.php?id=<?= intval($user['id']) ?>" title="Delete" onclick="return confirm('Are you sure?');">
                                    <i class="fa fa-eraser" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="alert alert-dark" role="alert">
                This is a dark alert—check it out!
            </div>
        <?php } ?>
    </div>
</body>
</html>
