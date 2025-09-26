<?php
require_once 'session_security.php';
require_once 'models/UserModel.php';
$userModel = new UserModel();

// Redis
$redis = new Redis();
$redis->connect('training-redis', 6379);

$login_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['submit'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate basic
    if ($username === '' || $password === '') {
        $login_error = 'Missing credentials';
    } else {
        // NOTE: UserModel::auth() should use prepared statements and password_verify()
        $user = $userModel->auth($username, $password);
        if ($user) {
            // login success: use secure_login()
            secure_login($user[0]['id'], ['name' => $user[0]['name']]);

            // save to Redis
            $redis->set('user:login:' . $user[0]['id'], json_encode($user[0]));
            $redis->expire('user:login:' . $user[0]['id'], 300);

            // set some safe localStorage items client-side and redirect
            $js_user_id = json_encode($user[0]['id']);
            $js_username = json_encode($user[0]['name']);
            echo "<!doctype html><html><head><meta charset='utf-8'><title>Redirecting</title></head><body>
                <script>
                    try {
                        localStorage.setItem('user_id', {$js_user_id});
                        localStorage.setItem('username', {$js_username});
                        localStorage.setItem('login_message', 'Login successful');
                    } catch(e) { /* ignore if storage blocked */ }
                    window.location.href = 'list_users.php';
                </script>
                </body></html>";
            exit;
        } else {
            $login_error = 'Login failed';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User form</title>
    <?php include 'views/meta.php' ?>
</head>
<body>
    <?php include 'views/header.php' ?>

    <div class="container">
        <div id="loginbox" style="margin-top:50px;" class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title">Login</div>
                    <div style="float:right; font-size: 80%; position: relative; top:-10px"><a href="#">Forgot password?</a></div>
                </div>

                <div style="padding-top:30px" class="panel-body">
                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger"><?= esc($_GET['error']) ?></div>
                    <?php endif; ?>
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?= esc($login_error) ?></div>
                    <?php endif; ?>

                    <form method="post" class="form-horizontal" role="form" autocomplete="off">
                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="login-username" type="text" class="form-control" name="username" value="<?= esc($_POST['username'] ?? '') ?>" placeholder="username or email" required>
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="login-password" type="password" class="form-control" name="password" placeholder="password" required>
                        </div>

                        <div class="margin-bottom-25">
                            <input type="checkbox" tabindex="3" class="" name="remember" id="remember">
                            <label for="remember"> Remember Me</label>
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <div class="col-sm-12 controls">
                                <button type="submit" name="submit" value="submit" class="btn btn-primary">Submit</button>
                                <a id="btn-fblogin" href="#" class="btn btn-primary">Login with Facebook</a>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12 control">
                                Don't have an account!
                                <a href="form_user.php">
                                    Sign Up Here
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
