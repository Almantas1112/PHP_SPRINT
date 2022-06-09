<?php
session_start();
echo '<link rel="stylesheet" href="./styles/styles.css">';

if (isset($_GET['action']) and $_GET['action'] == 'logout') {
    session_destroy();
    session_start();
}

$msg = '';
if(isset($_POST['login']) and !empty($_POST['username']) and !empty($_POST['password'])) {
    if($_POST['username'] == 'Prisijungimas' and $_POST['password'] == 'Slaptazodis') {
        $_SESSION['logged_in'] = true;
        $_SESSION['timeout'] = time();
        $_SESSION['username'] = $_POST['username'];
        $msg = 'Welcome!';
    } else {
        $msg = 'Wrong username or/and password!';
    }
}


if (isset($_SESSION['logged_in']) and $_SESSION['logged_in'] == true) {
    echo '';
} else {
    print('<h1>File system browser</h1>');
    echo '<h2>Enter Username and Password</h2>';
    echo '<form style="text-align: center; margin-top: 2rem;" action="./index.php" method="post">';
    echo '<input type="text" name="username" placeholder="Username = Prisijungimas" style="height: 2.5rem; width: 10rem; font-size: 0.8rem;" required autofocus>
<input type="password" name="password" placeholder="Password = Slaptazodis" style="height: 2.5rem; width: 10rem; font-size: 0.8rem;" required>
<button type="submit" name="login" style="width: 3rem; height: 2rem; font-size: 0.8rem;">Log in</button>
</form>';
echo '<div style="color: red; text-align: center; margin-top: 2rem;">'. $msg .'</div>';
}

if (isset($_SESSION['logged_in']) and $_SESSION['logged_in'] == true){

    $path = "./" . @$_GET["path"];
    $files = scandir($path);

    print('<h1>File system browser</h1>');
    
    print('<h2>Directory contents: ' . str_replace('?path=/','',$_SERVER['REQUEST_URI']) . '</h2>');
    
    print('<table><th>Type</th><th>Name</th><th>Actions</th>');
    if(is_array($files) || is_object($files)){
        foreach ($files as $find) {
            if ($find != ".." and $find != ".") {
                print('<tr>');
        
                print('<td>' . (is_dir($path . $find) ? "FOLDER" : "FILE") . '</td>');
                print('<td>' . (is_dir($path . $find)
                    ? '<a href="' . (isset($_GET['path'])
                    ? $_SERVER['REQUEST_URI'] . $find . '/'
                    : $_SERVER['REQUEST_URI'] . '?path=' . $find . '/') . '">' . $find . '</a>'
                    : $find) . '</td>');
                print('<td>' . (is_dir($path . $find)
                ? ''
                : '<form style="display: inline-block" action="" method="post">
                <input type="hidden" name="delete" value=' . str_replace(' ', '&nbsp;', $find) . '>
                <input type="submit" value="Delete" style="width: 5rem; height: 2rem; font-size: 1.4rem;">
                </form>
                <form style="display: inline-block" action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="download" value=' . str_replace(' ', '&nbsp;', $find) . '>
                <input type="submit" value="Download" style="width: 7rem; height: 2rem; font-size: 1.4rem;">
                </form>'
                ) . "</td>");
                print('</tr>');
            }
        }
    }    
    print ("</table>");
    
    if($path != "./"){
        print('<form style="text-align: center;" action="" method="post">
        <input type="submit" name="back" style="width: 8rem; height: 2rem; margin-top: 2rem; font-size: 1.4rem;" value=' . str_replace(' ', '&nbsp;', 'BACK') . ' required>
        </form>');
    }
    
    if (isset($_POST['download'])) {
        $download = $_POST['download'];

        if($download !== "index.php" and $download !== "styles.css"){
            $file_to_download = $path . $download;
            $file_escape = str_replace("&nbsp;", " ", htmlentities($file_to_download,0,'utf-8'));
            ob_clean();
            ob_start();
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename='.basename($file_escape));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: '.filesize($file_escape));
            ob_end_flush();
            readfile($file_escape);
            exit;
        } else {
            echo '<h2 style="color: red">You cannot download index.php or styles.css file!</h2>';
        }
    }

    if (isset($_POST['submit'])) {
        $file = $_FILES['file'];
    
        $file_name = $file['name'];
        $file_tmpName = $file['tmp_name'];
        $file_size = $file['size'];
        $file_size_limit = 20000000; // Allowed 20MB (20 000 000 B)
        $file_error = $file['error'];
        $file_type = $file['type'];
        $file_extension = explode('.', $file_name);
        $file_actual_extension = strtolower(end($file_extension));
    
        $allowed = ['jpg', 'jpeg', 'png', 'pdf']; // Allowed files to upload
    
        if (in_array($file_actual_extension, $allowed)) {
            if ($file_error === 0) {
                if ($file_size < $file_size_limit) {
                    $newFileName = uniqid('', true). "." .$file_actual_extension;
                    $fileDirectory = $path . $newFileName;
                    move_uploaded_file($file_tmpName, $fileDirectory);
                    header("Refresh: 0");
                } else {
                    echo 'Your file size is too big! ' . $file_size_limit . ' KB is the limit.';
                }
            } else {
                echo "Error appeared when uploading the file...";
            }
        } else {
            echo "This kind type of file not allowed, cannot upload it... sorry!";
        }
    }

    if(isset($_POST['back'])){
        header("Location:" . (dirname($_SERVER['REQUEST_URI'])). '/');
    }
    
    if(isset($_POST['delete'])) {
        $delete = $_POST['delete'];

        if($delete !== "index.php" and $delete !== "styles.css"){
            $modifiedDelete = preg_replace('/\s/u', ' ', $delete);
            unlink($path . $modifiedDelete);
            header("Refresh:0");
        } else {
            echo '<h2 style="color: red">Cannot delete style or index files</h2>';
        }
    }
    
    if(isset($_POST['create'])) {
        $create_file = $_POST['new_file'];
        if(str_contains($create_file , ".")){
            if (!file_exists($path . $create_file)){
                if(is_dir($path)){
                    fopen($path . $create_file, "w");
                    header("Refresh:0");
                }
            }
            else {
                print '<h2 style="color: red;"> File with same file name cannot be created</h2>';
            }
        }
        else {
            if(!file_exists($path . $create_file)){
                if(is_dir($path)){
                    mkdir($path . $create_file);
                    header("Refresh:0");
                }
            }
            else {
                print '<h2 style="color: red;"> Directory with same name is already created</h2>';
            }
        }
    }
    
    echo '<form method="post" action="" style="text-align: center; margin-top: 2rem;">
    <input type="text" name="new_file" placeholder="File or directory name" value="" style="width: 19rem; height: 4.5rem; font-size: 1.4rem;">
    <input type="submit" name="create" value="Submit" style="width: 9rem; height: 4rem; font-size: 1.2rem;">
    </form>';

    echo '<form action="" method="POST" style="text-align: center; margin-top: 2rem;" enctype="multipart/form-data">';
    echo '<input type="file" name="file">';
    echo '<button type="submit" name="submit">Upload</button>';

    echo '<h2>Click here to <a href="index.php?action=logout"> logout.</h2>';

}