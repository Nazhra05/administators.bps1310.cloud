<?php

if (empty($_SESSION['admin_id'])) {
    header('Location: ../public/login.php');
    exit;
}