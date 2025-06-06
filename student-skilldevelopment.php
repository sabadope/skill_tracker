<?php
    
    require_once "config/constants.php";
    require_once "config/database.php";
    require_once "includes/auth.php";

    // Use the same logic to construct the expected image filename
    $username = isset($_SESSION['username']) ? strtolower($_SESSION['username']) : 'default';
    $safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
    $imagePath = "../uploads/" . $safeUsername . ".png";

    // Fallback if image doesn't exist
    if (!file_exists($imagePath)) {
        $imagePath = "../uploads/default.png";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- My CSS -->
    <style>
        
        @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Poppins:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        a {
            text-decoration: none;
        }

        li {
            list-style: none;
        }

        :root {
            --poppins: 'Poppins', sans-serif;
            --lato: 'Lato', sans-serif;

            --light: #F9F9F9;
            --blue: #3C91E6;
            --light-blue: #CFE8FF;
            --grey: #eee;
            --dark-grey: #AAAAAA;
            --dark: #342E37;
            --red: #DB504A;
            --yellow: #FFCE26;
            --light-yellow: #FFF2C6;
            --orange: #FD7238;
            --light-orange: #FFE0D3;
        }

        html {
            overflow-x: hidden;
        }

        body.dark {
            --light: #0C0C1E;
            --grey: #060714;
            --dark: #FBFBFB;
        }

        body {
            background: var(--grey);
            overflow-x: hidden;
        }


        /* Target the entire page's scrollbar */
        ::-webkit-scrollbar {
            width: 6px; /* Set the width of the scrollbar */
            height: 6px; /* Set the height of the horizontal scrollbar (if needed) */
        }

        /* Style the track (the background of the scrollbar) */
        ::-webkit-scrollbar-track {
            background: #f1f1f1; /* Light background for the track */
            border-radius: 10px;
        }

        /* Style the thumb (the draggable part of the scrollbar) */
        ::-webkit-scrollbar-thumb {
            background: #888; /* Set the color of the thumb */
            border-radius: 10px; /* Round corners for the thumb */
        }

        /* Hover effect for the thumb */
        ::-webkit-scrollbar-thumb:hover {
            background: #555; /* Darker color when the user hovers over the thumb */
        }

        /* ========== SIDEBAR BASE ========== */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100%;
            background: var(--light);
            z-index: 2000;
            font-family: var(--lato);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease-in-out;
            overflow: hidden;
            scrollbar-width: none;
        }

        #sidebar::--webkit-scrollbar {
            display: none;
        }

        #sidebar.hide {
            width: 60px;
        }

        /* ========== BRAND ========== */
        #sidebar .brand {
            font-size: 24px;
            font-weight: 700;
            height: 56px;
            display: flex;
            align-items: center;
            color: var(--blue);
            position: sticky;
            top: 0;
            left: 0;
            background: var(--light);
            z-index: 500;
            box-sizing: content-box;
        }

        #sidebar .brand .bx {
            min-width: 60px;
            display: flex;
            justify-content: center;
        }

        /* ========== SIDEBAR CONTENT WRAPPER ========== */
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        /* ========== SIDE MENU BASE ========== */
        #sidebar .side-menu {
            width: 100%;
            margin-top: 48px;
        }

        #sidebar .side-menu li {
            height: 48px; /* or whatever height you set */
            background: transparent;
            margin-left: 6px;
            border-radius: 48px 0 0 48px;
            padding: 4px;
            transition: margin-top 0.2s ease; /* Smooth transition for margin adjustment */
        }

        #sidebar .side-menu li a {
            width: 100%;
            height: 100%;
            background: var(--light);
            display: flex;
            align-items: center;
            border-radius: 48px;
            font-size: 16px;
            color: var(--dark);
            white-space: nowrap;
        }


        /* Collapsed state */
        #sidebar.hide .side-menu li a .text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: all 0.3s ease;
            margin: 0;
            padding: 0;
        }

        /* Expanded state */
        #sidebar .side-menu li a .text {
            opacity: 1;
            visibility: visible;
            width: auto;
            transition: all 0.3s ease;
        }


        #sidebar.hide .sub-menu .text,
        #sidebar.hide .sub-menu i, {
            display: none;
            opacity: 0;
            visibility: hidden;
            width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: all 0.3s ease;
            margin: 0;
            padding: 0;
        }


        /* Hide the entire submenu only if it's not manually opened */
        .sidebar-collapsed .sub-menu:not(.active-manual) {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            height: 0;
            overflow: hidden;
            padding: 0;
        }



        #sidebar .side-menu li a .bx {
            min-width: calc(60px - ((4px + 6px) * 2));
            display: flex;
            justify-content: center;
        }

        /* ========== ACTIVE STATE ========== */
        #sidebar .side-menu li.active {
            background: var(--grey);
            position: relative;

        }

        /* Ensure submenu links have no background by default */
        #sidebar .sub-menu li a {
            background: none !important;
            color: var(--dark);
            transition: color 0.3s ease;
            margin-top: 3px;
        }

        /* On hover, only change the text color */
        #sidebar .sub-menu li a:hover {
            background: none !important;
            color: var(--dark) !important;
        }

        /* ===== Submenu Default Style ===== */
        #sidebar .side-menu .sub-menu li a {
            padding-left: 1px;       
            transition: color 0.3s;
            color: var(--dark);
        }

        #sidebar .side-menu .sub-menu li a:hover {
            color: var(--dark); /* Just change the text color on hover */
        }

        #sidebar .side-menu li.active::before,
        #sidebar .side-menu li.active::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            right: 0;
            z-index: -1;
        }

        #sidebar .side-menu li.active::before {
            top: -40px;
            box-shadow: 20px 20px 0 var(--grey);

        }

        #sidebar .side-menu li.active::after {
            bottom: -40px;
            box-shadow: 20px -20px 0 var(--grey);
        }

        #sidebar .side-menu.top li.active a {
            color: var(--blue);
        }

        /* ========== HOVER & HIDE EFFECTS ========== */
        #sidebar .side-menu.top li a:hover {
            color: var(--blue);
        }

        #sidebar.hide .side-menu li a {
            width: calc(48px - (4px * 2));
            transition: width 0.3s ease;
        }

        .sub-menu li a .underline i {
            padding-right: 20px; /* or 10px, 12px — adjust as needed */
        }

        .sub-menu li a .underline span {
            margin-left: -10px;
        }

        .sub-menu li a .underline {
            display: flex;
            align-items: center;
            border-bottom: 2px solid currentColor; /* Creates an underline that works for both icon and text */
            padding-bottom: 4px;
            padding-left: 0;
            
        }

        .sub-menu li a .non-underline i {
            padding-right: 20px; /* or 10px, 12px — adjust as needed */
            margin-top: -1px;
        }

        .sub-menu li a .non-underline span {
            margin-left: -10px;
        }

        .sub-menu li a .non-underline {
            display: flex;
            align-items: center;
            padding-left: 0;
            margin-top: -5px;
            
        }

        .sub-menu li a .underline.active {
            color: var(--blue); /* Optional: highlight color for active state */
            border-bottom: 2px solid var(--blue);
        }




        /* ========== LOGOUT COLOR ========== */
        #sidebar .side-menu li a.logout {
            color: var(--red);
        }

        #sidebar .side-menu li a.logout:hover {
            color: #bb2d3b;
        }

        /* ========== SUBMENU DROPDOWN ========== */
        .sub-menu {
            display: none;
            padding-left: 1.5rem;
            transition: all 0.3s ease;

        }

        /* Optional: Add active styles for the expanded submenu */
        .sub-menu.active {
            display: block; /* Ensure the submenu is visible */
        }

        /* Apply grey background to active items */
        .has-submenu.active > a {
            background: var(--grey);
            border-radius: 5px; /* Ensure the border radius is maintained */
        }

        /* Optionally, add hover effect to the active link */
        .has-submenu.active > a:hover {
            background: var(--grey); /* Active background for the Activities tab */
            border-radius: 5px; /* Preserve the border radius */
        }

        .has-submenu.active .sub-menu {
            display: block;
        }

        /* ========== Arrow for expanded/collapsed state ========== */
        .has-submenu > a .arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
            transform: rotate(0deg); /* Default: collapsed (arrow up) */
        }

        /* Arrow rotation for expanded submenu (handled by JavaScript now) */
        .has-submenu.active > a .arrow {
            transform: rotate(180deg); /* Expanded: arrow down */
        }

        .arrow {
            transition: transform 0.3s ease;
            display: inline-block; /* ensure transform works */
        }




        /* CONTENT */
        #content {
            position: relative;
            width: calc(100% - 280px);
            left: 280px;
            transition: .3s ease;
        }
        #sidebar.hide ~ #content {
            width: calc(100% - 60px);
            left: 60px;
        }

        /* NAVBAR */
        #content nav {
            height: 56px;
            background: var(--light);
            padding: 0 24px;
            display: flex;
            align-items: center;
            grid-gap: 24px;
            font-family: var(--lato);
            position: sticky;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        #content nav::before {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            bottom: -40px;
            left: 0;
            border-radius: 50%;
            box-shadow: -20px -20px 0 var(--light);
        }
        #content nav a {
            color: var(--dark);
        }
        #content nav .bx.bx-menu {
            cursor: pointer;
            color: var(--dark);
        }
        #content nav .nav-link {
            font-size: 16px;
            transition: .3s ease;
        }
        #content nav .nav-link:hover {
            color: var(--blue);
        }
        #content nav form {
            max-width: 400px;
            width: 100%;
            margin-right: auto;
        }
        #content nav form .form-input {
            display: flex;
            align-items: center;
            height: 36px;
        }
        #content nav form .form-input input {
            flex-grow: 1;
            padding: 0 16px;
            height: 100%;
            border: none;
            background: var(--grey);
            border-radius: 36px 0 0 36px;
            outline: none;
            width: 100%;
            color: var(--dark);
        }
        #content nav form .form-input button {
            width: 36px;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--blue);
            color: var(--light);
            font-size: 18px;
            border: none;
            outline: none;
            border-radius: 0 36px 36px 0;
            cursor: pointer;
        }
        #content nav .notification {
            font-size: 20px;
            position: relative;
        }
        #content nav .notification .num {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--light);
            background: var(--red);
            color: var(--light);
            font-weight: 700;
            font-size: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #content nav .profile img {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border-radius: 50%;
        }
        #content nav .switch-mode {
            display: block;
            min-width: 50px;
            height: 25px;
            border-radius: 25px;
            background: var(--grey);
            cursor: pointer;
            position: relative;
        }
        #content nav .switch-mode::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            bottom: 2px;
            width: calc(25px - 4px);
            background: var(--blue);
            border-radius: 50%;
            transition: all .3s ease;
        }
        #content nav #switch-mode:checked + .switch-mode::before {
            left: calc(100% - (25px - 4px) - 2px);
        }

        nav.navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #fff; /* Optional */
            position: relative;
        }

        /* Force the left and right parts to occupy equal width for balance */
        .nav-left,
        .nav-right {
            flex: 1;
            display: flex;
            align-items: center;

        }

        /* Right section spacing */
        .nav-right {
            justify-content: flex-end;
            gap: 30px;
            margin-left: 16%;
        }

        /* Center part (search bar) stays in the middle */
        .nav-center {
            flex: 0 0 auto;
            display: flex;
            justify-content: space-between;
        }

        /* Search form styling */
        .form-input {
            display: flex;
            align-items: center;
            background: #f1f1f1;
            padding: 0;
            border-radius: 20px;
            width: 100%;
        }

        .form-input input[type="search"] {
            border: none;
            outline: none;
            background: transparent;
            padding: 5px 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;

        }

        .search-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #333;
        }

        /* Profile image */
        .profile img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .chevron-toggle {
            font-size: 25px;
            color: var(--blue);
            cursor: pointer;
            transition: transform 0.3s ease, color 0.3s ease;
        }
        /* NAVBAR */





        /* MAIN */
        #content main {
            width: 100%;
            padding: 36px 24px;
            font-family: var(--poppins);
            max-height: calc(100vh - 56px);
            overflow-y: auto;
        }
        #content main .head-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            grid-gap: 16px;
            flex-wrap: wrap;
        }
        #content main .head-title .left h1 {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        #content main .head-title .left .breadcrumb {
            display: flex;
            align-items: center;
            grid-gap: 16px;
        }
        #content main .head-title .left .breadcrumb li {
            color: var(--dark);
        }
        #content main .head-title .left .breadcrumb li a {
            color: var(--dark-grey);
            
        }
        #content main .head-title .left .breadcrumb li a.active {
            color: var(--blue);
            pointer-events: unset;
        }
        #content main .head-title .btn-download {
            height: 36px;
            padding: 0 16px;
            border-radius: 36px;
            background: var(--blue);
            color: var(--light);
            display: flex;
            justify-content: center;
            align-items: center;
            grid-gap: 10px;
            font-weight: 500;
        }




        #content main .box-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            grid-gap: 24px;
            margin-top: 24px;
        }

        #content main .box-info li {
            padding: 24px;
            background: var(--light);
            border-radius: 20px;
            display: flex;
            align-items: center;
            grid-gap: 24px;
            position: relative;
        }

        #content main .box-info li:hover {
            background: var(--blue);     /* Hover background color */
            outline: 2px solid var(--dark); /* Solid outline outside the box */
            border-radius: 20px;         /* Border radius for rounded corners */
            cursor: pointer;            /* Pointer cursor on hover */
            
            /* Prevent any movement */
            box-shadow: none;            /* No shadow to prevent layout change */
            transform: none;             /* Ensure no transformation or movement */
            
            /* Change text color on hover */
            color: var(--light);         /* Change text color to light when hovering */
        }

        #content main .box-info li .bx {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            font-size: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #content main .box-info li:nth-child(1) .bx {
            background: var(--light-blue);
            color: var(--blue);
        }
        #content main .box-info li:nth-child(2) .bx {
            background: var(--light-blue);
            color: var(--blue);
        }
        
        /* Text styles */
        #content main .box-info li .text h3 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);  /* Default color */
            
        }

        #content main .box-info li .text p {
            color: var(--dark);  /* Default color */
            
        }

        /* Hover effect on text */
        #content main .box-info li:hover .text h3 {
            color: var(--light);   /* Text color on hover */
        }

        #content main .box-info li:hover .text p {
            color: var(--light);   /* Text color on hover */
        }





        #content main .table-data {
            display: flex;
            flex-wrap: wrap;
            grid-gap: 24px;
            margin-top: 24px;
            width: 100%;
            color: var(--dark);
        }
        #content main .table-data > div {
            border-radius: 20px;
            background: var(--light);
            padding: 24px;
            overflow-x: auto;
        }
        #content main .table-data .head {
            display: flex;
            align-items: center;
            grid-gap: 16px;
            margin-bottom: 24px;
        }
        #content main .table-data .head h3 {
            margin-right: auto;
            font-size: 24px;
            font-weight: 600;
        }
        #content main .table-data .head .bx {
            cursor: pointer;
        }

        #content main .table-data .order {
            flex-grow: 1;
            flex-basis: 500px;
        }
        #content main .table-data .order table {
            width: 100%;
            border-collapse: collapse;
        }
        #content main .table-data .order table th {
            padding-bottom: 12px;
            font-size: 13px;
            text-align: left;
            border-bottom: 1px solid var(--grey);
        }
        #content main .table-data .order table td {
            padding: 16px 0;
        }
        #content main .table-data .order table tr td:first-child {
            display: flex;
            align-items: center;
            grid-gap: 12px;
            padding-left: 6px;
        }
        #content main .table-data .order table td img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        #content main .table-data .order table tbody tr:hover {
            background: var(--grey);
        }
        #content main .table-data .order table tr td .status {
            font-size: 10px;
            padding: 6px 16px;
            color: var(--light);
            border-radius: 20px;
            font-weight: 700;
        }
        #content main .table-data .order table tr td .status.completed {
            background: var(--blue);
        }
        #content main .table-data .order table tr td .status.process {
            background: var(--yellow);
        }
        #content main .table-data .order table tr td .status.pending {
            background: var(--orange);
        }


        #content main .table-data .todo {
            flex-grow: 1;
            flex-basis: 300px;
        }
        #content main .table-data .todo .todo-list {
            width: 100%;
        }
        #content main .table-data .todo .todo-list li {
            width: 100%;
            margin-bottom: 16px;
            background: var(--grey);
            border-radius: 10px;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #content main .table-data .todo .todo-list li .bx {
            cursor: pointer;
        }
        #content main .table-data .todo .todo-list li.completed {
            border-left: 10px solid var(--blue);
        }
        #content main .table-data .todo .todo-list li.not-completed {
            border-left: 10px solid var(--orange);
        }
        #content main .table-data .todo .todo-list li:last-child {
            margin-bottom: 0;
        }
        /* MAIN */
        /* CONTENT */









        @media screen and (max-width: 768px) {
            #sidebar {
                width: 200px;
            }

            #content {
                width: calc(100% - 60px);
                left: 200px;
            }

            #content nav .nav-link {
                display: none;
            }
        }






        @media screen and (max-width: 576px) {
            #content nav form .form-input input {
                display: none;
            }

            #content nav form .form-input button {
                width: auto;
                height: auto;
                background: transparent;
                border-radius: none;
                color: var(--dark);
            }

            #content nav form.show .form-input input {
                display: block;
                width: 100%;
            }
            #content nav form.show .form-input button {
                width: 36px;
                height: 100%;
                border-radius: 0 36px 36px 0;
                color: var(--light);
                background: var(--red);
            }

            #content nav form.show ~ .notification,
            #content nav form.show ~ .profile {
                display: none;
            }

            #content main .box-info {
                grid-template-columns: 1fr;
            }

            #content main .table-data .head {
                min-width: 420px;
            }
            #content main .table-data .order table {
                min-width: 420px;
            }
            #content main .table-data .todo .todo-list {
                min-width: 420px;
            }
        }

        /* Ensure the container remains fixed in size */
        .recent-accounts {
            width: 100%;
            max-width: 100%;
            height: 330px; /* Fixed height to prevent stretching */
            overflow: hidden; /* Prevent content from overflowing */
            position: relative;
        }

        /* Table and Chart Container */
        .table-view, .chart-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 0;
            left: 0;
            
        }

        /* Hide Pie Chart Initially */
        .chart-container {
            display: none;
            
        }

        /* Ensure the Pie Chart adjusts inside the container */
        canvas {
            max-width: 100% !important;
            max-height: 100% !important;
            
        }
    </style>

    <title>Student Performance</title>
</head>
<body>


    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class="bx bxs-graduation"></i>
            <span class="text">Student Panel</span>
        </a>

        <!-- NEW FLEX WRAPPER -->
        <div class="sidebar-content">
            <!-- TOP ITEMS -->
            <ul class="side-menu top">
                <li>
                    <a href="student-activities.php" style="display: flex; align-items: center;">
                        <i class='bx bxs-folder-open'></i>
                        <span class="text">Activities</span>
                        <i class='bx bx-chevron-down arrow' style="margin-left: auto;"></i>
                    </a>
                </li>
                <li>
                    <a href="student-attendance.php">
                        <i class='bx bxs-calendar-check'></i>
                        <span class="text">Attendance</span>
                    </a>
                </li>
                <li>
                    <a href="student-messages.php">
                        <i class='bx bxs-message-dots'></i>
                        <span class="text">Message</span>
                    </a>
                </li>

                <!-- Performance with Submenu -->
                <li id="performance-submenu" class="has-submenu">
                    <a href="student-performance.php">
                        <i class='bx bxs-book-content'></i>
                        <span class="text">Performance</span>
                        <i class='bx bx-chevron-down arrow'></i>
                    </a>
                    <ul id="sub-menu" class="sub-menu active">
                        <li>
                            <a href="student-skilldevelopment.php">
                                <div class="underline">
                                    <i class='bx bx-check-shield'></i>
                                    <span>Skill Development</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="student-taskcompletion.php">
                                <div class="non-underline">
                                    <i class='bx bx-task'></i>
                                    <span>Task Completion</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="student-behavioralconduct.php">
                                <div class="non-underline">
                                    <i class='bx bx-check-circle'></i>
                                    <span>Behavioral Conduct</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="student-achievementranking.php">
                                <div class="non-underline">
                                    <i class='bx bx-check-square'></i>
                                    <span>Achievement Ranking</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="#">
                        <i class='bx bxs-cog'></i>
                        <span class="text">Settings</span>
                    </a>
                </li>
                <li>
                    <a href="student-logout.php" class="logout">
                        <i class='bx bxs-log-out-circle'></i>
                        <span class="text">Logout</span>
                    </a>
                </li>
            </ul>

        </div>
    </section>





    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav class="navbar">
            <i class="bx bx-chevron-left chevron-toggle"></i> <!-- Sidebar toggle button -->
            <div class="nav-left"></div>

            <!-- Center: Search Form -->
            <form action="#" class="nav-center">
                <div class="form-input">
                    <input type="search" placeholder="Search...">
                    <button type="submit" class="search-btn">
                        <i class='bx bx-search'></i>
                    </button>
                </div>
            </form>

            <div class="nav-right">
                <input type="checkbox" id="switch-mode" hidden>
                <label for="switch-mode" class="switch-mode"></label>
                
                <div class="profile">
                    <img src="<?php echo $imagePath; ?>" alt="Profile Image" width="40" height="40" style="border-radius: 50%; object-fit: cover;">
                </div>
            </div>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Performance</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="student-performance.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right' ></i></li>
                        <li>
                            <a class="active">Skill Development</a>
                        </li>
                    </ul>
                </div>
                
            </div>

            <div class="table-data">

                <!-- YOUR IFRAMES HERE -->
                 <!-- Chat Container -->
                <div class="chat-container" style="overflow-x: hidden;">

                    <iframe src="login.php" max-width="980px" width="980px" height="600px" frameborder="0" style="overflow-x: hidden;"></iframe>

                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->


    <!-- NAV BAR W/ TOGGLE HIDE -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleSidebarBtn = document.querySelector('.navbar i.bx');
        const allSideMenuLinks = document.querySelectorAll('#sidebar .side-menu.top li a');
        const submenuToggle = document.querySelector('.submenu-toggle');
        const subMenu = document.getElementById('sub-menu');

        allSideMenuLinks.forEach(item => {
            const li = item.parentElement;

            item.addEventListener('click', () => {
                allSideMenuLinks.forEach(i => {
                    i.parentElement.classList.remove('active');
                });
                li.classList.add('active');
            });
        });

        // Toggle chevron direction
        if (sidebar.classList.contains('hide')) {
            toggleSidebarBtn.classList.replace('bx-chevron-left', 'bx-chevron-right');
        } else {
            toggleSidebarBtn.classList.replace('bx-chevron-right', 'bx-chevron-left');
        }

        toggleSidebarBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hide');
            const isCollapsed = sidebar.classList.contains('hide');

            if (isCollapsed) {
                sidebar.classList.add('sidebar-collapsed');

                // Collapse all submenus and save their state
                document.querySelectorAll('.has-submenu').forEach(item => {
                    const submenu = item.querySelector('.sub-menu');
                    const arrow = item.querySelector('.arrow');
                    const nextLi = item.nextElementSibling;

                    const isExpanded = submenu.classList.contains('active');
                    item.setAttribute('data-opened', isExpanded ? 'true' : 'false');

                    submenu.classList.remove('active');
                    submenu.style.display = 'none';

                    if (arrow) arrow.style.transform = 'rotate(0deg)';
                    if (nextLi) nextLi.style.marginTop = '0px';
                });

            } else {
                sidebar.classList.remove('sidebar-collapsed');

                // Restore submenus that were previously open
                document.querySelectorAll('.has-submenu').forEach(item => {
                    const shouldOpen = item.getAttribute('data-opened') === 'true';
                    const submenu = item.querySelector('.sub-menu');
                    const arrow = item.querySelector('.arrow');
                    const nextLi = item.nextElementSibling;

                    if (shouldOpen) {
                        submenu.classList.add('active');
                        submenu.style.display = 'block';

                        if (arrow) arrow.style.transform = 'rotate(180deg)';
                        if (nextLi) nextLi.style.marginTop = '185px';
                    }
                });

                // Restore manual submenu
                if (subMenu.classList.contains('active')) {
                    subMenu.style.display = 'block';
                }
            }

            // Toggle chevron direction
            if (sidebar.classList.contains('hide')) {
                toggleSidebarBtn.classList.replace('bx-chevron-left', 'bx-chevron-right');
            } else {
                toggleSidebarBtn.classList.replace('bx-chevron-right', 'bx-chevron-left');
            }

        });

        // Submenu toggle for manual expand/collapse
        submenuToggle.addEventListener('click', (e) => {
            e.preventDefault();

            const isOpen = subMenu.classList.contains('active-manual');

            if (isOpen) {
                subMenu.classList.remove('active', 'active-manual');
                subMenu.style.display = 'none';
            } else {
                subMenu.classList.add('active', 'active-manual');
                subMenu.style.display = 'block';
            }
        });

        window.addEventListener('DOMContentLoaded', () => {
            const isSidebarCollapsed = sidebar.classList.contains('hide');
            const isManuallyOpened = subMenu.classList.contains('active-manual');

            if (isSidebarCollapsed && !isManuallyOpened) {
                subMenu.style.display = 'none';
            }
        });

        // Search bar for mobile screens
        const searchButton = document.querySelector('#content nav form .form-input button');
        const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
        const searchForm = document.querySelector('#content nav form');

        searchButton.addEventListener('click', function (e) {
            if (window.innerWidth < 576) {
                e.preventDefault();
                searchForm.classList.toggle('show');
                if (searchForm.classList.contains('show')) {
                    searchButtonIcon.classList.replace('bx-search', 'bx-x');
                } else {
                    searchButtonIcon.classList.replace('bx-x', 'bx-search');
                }
            }
        });
    </script>


    <!-- SIDEBAR FUNCTIONALITIES -->
    <script>
        // ========== DEFAULT ACTIVATION RULES FOR ACTIVITIES & PERFORMANCE ==========

        const path = window.location.pathname;

        if (
            path.includes("student-activities.php") ||
            path.includes("student-performance.php") ||
            path.includes("student-skilldevelopment.php") // 👈 Add this line

        ) {
            const menuId = (path.includes("student-performance.php") || path.includes("student-skilldevelopment.php"))
                ? '#performance-submenu'
                : '#activities-submenu';

            const menuElement = document.querySelector(menuId);
            const submenu = menuElement.querySelector('.sub-menu');
            const nextLi = menuElement.nextElementSibling;

            // Keep the tab styled as active
            menuElement.classList.add('active');

            // Keep the submenu expanded
            submenu.classList.add('active');
            submenu.style.display = 'block';

            // Push down the next item to avoid overlap
            if (nextLi) nextLi.style.marginTop = '185px';
        }

        // ========== SUBMENU TOGGLE FUNCTIONALITY ==========

        const submenuLinks = document.querySelectorAll('.has-submenu > a');

        submenuLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent redirect

                const parentLi = link.parentElement;
                const submenu = parentLi.querySelector('.sub-menu');
                const arrow = link.querySelector('.arrow'); // Get the arrow element
                const nextLi = parentLi.nextElementSibling;

                // Toggle submenu visibility
                const isExpanded = submenu.classList.contains('active');
                submenu.classList.toggle('active');
                submenu.style.display = isExpanded ? 'none' : 'block';

                // Rotate arrow based on expanded/collapsed state
                arrow.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';

                // Adjust margin of next item
                if (nextLi) nextLi.style.marginTop = isExpanded ? '0px' : '185px';
            });
        });

        // ========== HIGHLIGHT ACTIVE SUBMENU ITEM ==========
        if (path.includes("student-skilldevelopment.php")) {
            const skillLink = document.querySelector('.sub-menu li a[href="student-skilldevelopment.php"]');
            if (skillLink) {
                const underlineDiv = skillLink.querySelector('.underline');
                if (underlineDiv) {
                    underlineDiv.classList.add('active');
                }
            }
        }
    </script>

    <!-- NIGHT MODE -->
    <script>
        const switchMode = document.getElementById('switch-mode');

        // On page load, check localStorage and apply mode
        window.addEventListener('DOMContentLoaded', () => {
            const darkModeEnabled = localStorage.getItem('dark-mode') === 'true';

            switchMode.checked = darkModeEnabled; // update the toggle position
            document.body.classList.toggle('dark', darkModeEnabled); // apply dark mode if enabled
        });

        // When user toggles the switch
        switchMode.addEventListener('change', function () {
            if (this.checked) {
                document.body.classList.add('dark');
                localStorage.setItem('dark-mode', 'true'); // store preference
            } else {
                document.body.classList.remove('dark');
                localStorage.setItem('dark-mode', 'false'); // store preference
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Load Chart.js -->
</body>
</html>