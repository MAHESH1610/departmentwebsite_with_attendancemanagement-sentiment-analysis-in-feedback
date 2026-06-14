<?php
session_start();
require_once 'scholarship_helpers.php';
require_once 'emotion_helpers.php';

if (!isset($_SESSION['hod_id'])) {
    header("Location: staff-login.html");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "depart_db", 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function ensureColumn($conn, $table, $column, $definition)
{
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE $table ADD COLUMN $column $definition");
    }
}

$classOptions = array(
    "1st Year MCA",
    "2nd Year MCA",
    "1st Year MSc CS",
    "2nd Year MSc CS",
    "1st Year MSc IT",
    "2nd Year MSc IT"
);

$conn->query("CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    class_name VARCHAR(80) DEFAULT NULL,
    department VARCHAR(120) DEFAULT 'Computer Science',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS staff (
    staff_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    department VARCHAR(120) DEFAULT 'Computer Science',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureColumn($conn, "students", "name", "VARCHAR(150) NOT NULL DEFAULT ''");
ensureColumn($conn, "students", "password", "VARCHAR(100) NOT NULL DEFAULT ''");
ensureColumn($conn, "students", "email", "VARCHAR(150) DEFAULT NULL");
ensureColumn($conn, "students", "class_name", "VARCHAR(80) DEFAULT NULL");
ensureColumn($conn, "students", "department", "VARCHAR(120) DEFAULT 'Computer Science'");
ensureColumn($conn, "staff", "name", "VARCHAR(150) NOT NULL DEFAULT ''");
ensureColumn($conn, "staff", "password", "VARCHAR(100) NOT NULL DEFAULT ''");
ensureColumn($conn, "staff", "email", "VARCHAR(150) DEFAULT NULL");
ensureColumn($conn, "staff", "department", "VARCHAR(120) DEFAULT 'Computer Science'");

$conn->query("CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    period VARCHAR(10) NOT NULL,
    status VARCHAR(10) NOT NULL,
    class_name VARCHAR(80) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
ensureColumn($conn, "attendance", "class_name", "VARCHAR(80) DEFAULT NULL");

$conn->query("CREATE TABLE IF NOT EXISTS department_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS department_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    event_date DATE NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS teacher_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    teacher_name VARCHAR(150) NOT NULL,
    feedback_text TEXT NOT NULL,
    emotion VARCHAR(50) DEFAULT 'Not analyzed',
    video_emotion VARCHAR(50) DEFAULT 'Not analyzed',
    video_path VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureColumn($conn, "department_achievements", "image_path", "VARCHAR(255)");
ensureColumn($conn, "department_events", "image_path", "VARCHAR(255)");
ensureColumn($conn, "teacher_feedback", "emotion", "VARCHAR(50) DEFAULT 'Not analyzed'");
ensureColumn($conn, "teacher_feedback", "video_emotion", "VARCHAR(50) DEFAULT 'Not analyzed'");
ensureTeacherFeedbackEmotionColumn($conn);
backfillFeedbackEmotions($conn);
backfillFeedbackVideoEmotions($conn);
ensureScholarshipApplicationsTable($conn);
$conn->query("ALTER TABLE attendance MODIFY status VARCHAR(10) NOT NULL");

$message = "";

function saveUploadedFile($fieldName, $targetFolder, $allowedTypes)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return "";
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return "";
    }

    $mimeType = mime_content_type($_FILES[$fieldName]['tmp_name']);
    if (!in_array($mimeType, $allowedTypes, true)) {
        return "";
    }

    if (!is_dir($targetFolder)) {
        mkdir($targetFolder, 0777, true);
    }

    $extension = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $fileName = uniqid($fieldName . "_", true) . "." . strtolower($extension);
    $targetPath = $targetFolder . "/" . $fileName;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return $targetPath;
    }

    return "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === "add_student") {
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['student_name'] ?? '');
        $password = trim($_POST['student_password'] ?? '');
        $email = trim($_POST['student_email'] ?? '');
        $className = trim($_POST['student_class'] ?? '');

        if ($student_id !== '' && $name !== '' && $password !== '' && in_array($className, $classOptions, true)) {
            $stmt = $conn->prepare("INSERT INTO students (student_id, name, password, email, class_name)
                                    VALUES (?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), email = VALUES(email), class_name = VALUES(class_name)");
            $stmt->bind_param("sssss", $student_id, $name, $password, $email, $className);
            $stmt->execute();
            $stmt->close();
            $message = "Student account saved. The student is now available in the " . $className . " attendance list.";
        } else {
            $message = "Please enter student ID, name, password, and class.";
        }
    }

    if ($action === "delete_student") {
        $student_id = trim($_POST['student_id'] ?? '');

        if ($student_id !== '') {
            $conn->begin_transaction();

            try {
                foreach (array("attendance", "scholarship_applications", "teacher_feedback") as $relatedTable) {
                    $stmt = $conn->prepare("DELETE FROM $relatedTable WHERE student_id = ?");
                    $stmt->bind_param("s", $student_id);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                $deleted = $stmt->affected_rows;
                $stmt->close();

                $conn->commit();
                $message = ($deleted > 0) ? "Student account deleted successfully." : "Student account was not found.";
            } catch (Throwable $exception) {
                $conn->rollback();
                $message = "Student account could not be deleted.";
            }
        } else {
            $message = "Student ID is required to delete an account.";
        }
    }

    if ($action === "add_staff") {
        $staff_id = trim($_POST['staff_id'] ?? '');
        $name = trim($_POST['staff_name'] ?? '');
        $password = trim($_POST['staff_password'] ?? '');
        $email = trim($_POST['staff_email'] ?? '');

        if ($staff_id !== '' && $name !== '' && $password !== '') {
            $stmt = $conn->prepare("INSERT INTO staff (staff_id, name, password, email)
                                    VALUES (?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), email = VALUES(email)");
            $stmt->bind_param("ssss", $staff_id, $name, $password, $email);
            $stmt->execute();
            $stmt->close();
            $message = "Staff account saved successfully.";
        } else {
            $message = "Please enter staff ID, name, and password.";
        }
    }

    if ($action === "delete_staff") {
        $staff_id = trim($_POST['staff_id'] ?? '');

        if ($staff_id !== '') {
            $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
            $stmt->bind_param("s", $staff_id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();
            $message = ($deleted > 0) ? "Staff account deleted successfully." : "Staff account was not found.";
        } else {
            $message = "Staff ID is required to delete an account.";
        }
    }

    if ($action === "change_hod_password") {
        $currentPassword = trim($_POST['current_hod_password'] ?? '');
        $newPassword = trim($_POST['new_hod_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_hod_password'] ?? '');
        $hodId = $_SESSION['hod_id'];

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $message = "Please fill all HOD password fields.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New HOD password and confirm password do not match.";
        } else {
            $stmt = $conn->prepare("SELECT hod_id FROM hod_users WHERE hod_id = ? AND password = ?");
            $stmt->bind_param("ss", $hodId, $currentPassword);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result && $result->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE hod_users SET password = ? WHERE hod_id = ?");
                $stmt->bind_param("ss", $newPassword, $hodId);
                $stmt->execute();
                $stmt->close();
                $message = "HOD password updated successfully.";
            } else {
                $message = "Current HOD password is incorrect.";
            }
        }
    }

    if ($action === "add_achievement") {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_path = saveUploadedFile("achievement_image", "uploads/achievements", array(
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"
        ));

        if ($title !== '') {
            $stmt = $conn->prepare("INSERT INTO department_achievements (title, description, image_path) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $description, $image_path);
            $stmt->execute();
            $stmt->close();
            $message = "Achievement updated successfully.";
        }
    }

    if ($action === "add_event") {
        $title = trim($_POST['title'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $image_path = saveUploadedFile("event_image", "uploads/events", array(
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"
        ));

        if ($title !== '' && $event_date !== '') {
            $stmt = $conn->prepare("INSERT INTO department_events (title, event_date, description, image_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $title, $event_date, $description, $image_path);
            $stmt->execute();
            $stmt->close();
            $message = "Upcoming event added successfully.";
        }
    }

    if ($action === "mark_od") {
        $records = $_POST['attendance_records'] ?? array();
        $updated = 0;

        foreach ($records as $record) {
            $parts = explode("|", $record);
            if (count($parts) !== 3) {
                continue;
            }

            $student_id = $parts[0];
            $date = $parts[1];
            $period = $parts[2];

            $stmt = $conn->prepare("UPDATE attendance SET status = 'OD' WHERE student_id = ? AND date = ? AND period = ? AND status = 'A'");
            $stmt->bind_param("sss", $student_id, $date, $period);
            $stmt->execute();
            $updated += $stmt->affected_rows;
            $stmt->close();
        }

        $message = $updated . " attendance record(s) converted to OD.";
    }
}

$achievements = $conn->query("SELECT * FROM department_achievements ORDER BY achievement_id DESC LIMIT 5");
$events = $conn->query("SELECT * FROM department_events ORDER BY event_date ASC, event_id DESC LIMIT 5");
$feedback = $conn->query("SELECT f.*, s.name
                          FROM teacher_feedback f
                          LEFT JOIN students s ON s.student_id = f.student_id
                          ORDER BY f.submitted_at DESC
                          LIMIT 30");
$absentRecords = $conn->query("SELECT a.student_id, s.name, a.date, a.period, a.status
                               FROM attendance a
                               LEFT JOIN students s ON s.student_id = a.student_id
                               WHERE a.status = 'A'
                               ORDER BY a.date DESC, a.period DESC
                               LIMIT 50");
$scholarshipApplications = $conn->query("SELECT *
                                         FROM scholarship_applications
                                         ORDER BY applied_at DESC
                                         LIMIT 50");
$students = $conn->query("SELECT student_id, name, email, class_name
                          FROM students
                          ORDER BY class_name ASC, student_id ASC
                          LIMIT 100");
$staffUsers = $conn->query("SELECT staff_id, name, email
                            FROM staff
                            ORDER BY staff_id ASC
                            LIMIT 100");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard</title>
    <link rel="stylesheet" href="hod-dashboard.css?v=20260602">
</head>
<body>
    <main class="container">
        <h1>HOD Dashboard</h1>
        <p>Manage student and staff accounts, attendance access, achievements, events, and OD updates.</p>

        <?php if ($message !== "") { ?>
            <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
        <?php } ?>

        <div class="page-actions">
            <a href="index.html" class="back-btn">Back to Home</a>
            <a href="logout.php" class="back-btn">Logout</a>
        </div>

        <section class="dashboard-grid">
            <form method="POST" class="panel">
                <input type="hidden" name="action" value="add_student">
                <h2>Create Student Login</h2>
                <input type="text" name="student_id" placeholder="Student ID" required>
                <input type="text" name="student_name" placeholder="Student name" required>
                <input type="password" name="student_password" placeholder="Password" required>
                <select name="student_class" required>
                    <option value="">Select Class</option>
                    <?php foreach ($classOptions as $classOption) { ?>
                        <option value="<?php echo htmlspecialchars($classOption); ?>"><?php echo htmlspecialchars($classOption); ?></option>
                    <?php } ?>
                </select>
                <input type="email" name="student_email" placeholder="Email address">
                <button type="submit">Save Student</button>
            </form>

            <form method="POST" class="panel">
                <input type="hidden" name="action" value="add_staff">
                <h2>Create Staff Login</h2>
                <input type="text" name="staff_id" placeholder="Staff ID" required>
                <input type="text" name="staff_name" placeholder="Staff name" required>
                <input type="password" name="staff_password" placeholder="Password" required>
                <input type="email" name="staff_email" placeholder="Email address">
                <button type="submit">Save Staff</button>
            </form>
        </section>

        <section class="dashboard-grid">
            <div class="panel">
                <h2>Students in Attendance List</h2>
                <table>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($students && $students->num_rows > 0) { ?>
                        <?php while ($row = $students->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['class_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                            <td>
                                <form method="POST" class="inline-action" onsubmit="return confirm('Delete this student account?');">
                                    <input type="hidden" name="action" value="delete_student">
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['student_id']); ?>">
                                    <button type="submit" class="danger-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5">No students added yet.</td>
                        </tr>
                    <?php } ?>
                </table>
            </div>

            <div class="panel">
                <h2>Staff Login Accounts</h2>
                <table>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($staffUsers && $staffUsers->num_rows > 0) { ?>
                        <?php while ($row = $staffUsers->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['staff_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                            <td>
                                <form method="POST" class="inline-action" onsubmit="return confirm('Delete this staff account?');">
                                    <input type="hidden" name="action" value="delete_staff">
                                    <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars($row['staff_id']); ?>">
                                    <button type="submit" class="danger-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="4">No staff accounts added yet.</td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Change HOD Password</h2>
            <form method="POST" class="compact-form">
                <input type="hidden" name="action" value="change_hod_password">
                <input type="password" name="current_hod_password" placeholder="Current password" required>
                <input type="password" name="new_hod_password" placeholder="New password" required>
                <input type="password" name="confirm_hod_password" placeholder="Confirm new password" required>
                <button type="submit">Update Password</button>
            </form>
        </section>

        <section class="dashboard-grid">
            <form method="POST" class="panel" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_achievement">
                <h2>Update Achievements</h2>
                <input type="text" name="title" placeholder="Achievement title" required>
                <textarea name="description" placeholder="Short description"></textarea>
                <label class="file-label" for="achievement_image">Upload achievement image</label>
                <input id="achievement_image" type="file" name="achievement_image" accept="image/*">
                <button type="submit">Save Achievement</button>
            </form>

            <form method="POST" class="panel" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_event">
                <h2>Add Upcoming Event</h2>
                <input type="text" name="title" placeholder="Event title" required>
                <input type="date" name="event_date" required>
                <textarea name="description" placeholder="Event details"></textarea>
                <label class="file-label" for="event_image">Upload event image</label>
                <input id="event_image" type="file" name="event_image" accept="image/*">
                <button type="submit">Save Event</button>
            </form>
        </section>

        <section class="panel">
            <h2>Student Teacher Feedback</h2>
            <p>Review feedback submitted by students for teachers.</p>

            <?php if ($feedback && $feedback->num_rows > 0) { ?>
                <div class="feedback-list">
                    <?php while ($row = $feedback->fetch_assoc()) { ?>
                        <article class="feedback-card">
                            <h3><?php echo htmlspecialchars($row['teacher_name']); ?></h3>
                            <p><strong>Student:</strong> <?php echo htmlspecialchars($row['student_id']); ?> <?php echo htmlspecialchars($row['name'] ? '(' . $row['name'] . ')' : ''); ?></p>
                            <p><?php echo htmlspecialchars($row['feedback_text']); ?></p>
                            <p><strong>Text Emotion:</strong> <span class="emotion-badge"><?php echo htmlspecialchars($row['emotion'] ?? 'Not analyzed'); ?></span></p>
                            <p><strong>Video Emotion:</strong> <span class="emotion-badge"><?php echo htmlspecialchars($row['video_emotion'] ?? 'Not analyzed'); ?></span></p>
                            <p class="small-text"><?php echo htmlspecialchars($row['submitted_at']); ?></p>
                            <?php if (!empty($row['video_path'])) { ?>
                                <video controls src="<?php echo htmlspecialchars($row['video_path']); ?>"></video>
                            <?php } ?>
                        </article>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p>No teacher feedback submitted yet.</p>
            <?php } ?>
        </section>

        <section class="panel">
            <h2>Scholarship Applications</h2>
            <p>Review scholarship applicants and their ML eligibility status.</p>

            <table>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Scholarship</th>
                    <th>Marks</th>
                    <th>Attendance</th>
                    <th>Backlogs</th>
                    <th>Income</th>
                    <th>Sports</th>
                    <th>Status</th>
                </tr>
                <?php if ($scholarshipApplications && $scholarshipApplications->num_rows > 0) { ?>
                    <?php while ($row = $scholarshipApplications->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['application_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['scholarship_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['marks']); ?>%</td>
                        <td><?php echo htmlspecialchars($row['attendance']); ?>%</td>
                        <td><?php echo htmlspecialchars($row['backlogs']); ?></td>
                        <td><?php echo htmlspecialchars($row['income']); ?></td>
                        <td><?php echo ((int)$row['sports_or_not'] === 1) ? 'Yes' : 'No'; ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="10">No scholarship applications found.</td>
                    </tr>
                <?php } ?>
            </table>
        </section>

        <section class="panel">
            <h2>Convert Absent Attendance to OD</h2>
            <p>Select absent records posted by staff and convert them into OD.</p>

            <form method="POST">
                <input type="hidden" name="action" value="mark_od">
                <table>
                    <tr>
                        <th>Select</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Period</th>
                        <th>Status</th>
                    </tr>
                    <?php if ($absentRecords && $absentRecords->num_rows > 0) { ?>
                        <?php while ($row = $absentRecords->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <input type="checkbox"
                                       name="attendance_records[]"
                                       value="<?php echo htmlspecialchars($row['student_id'] . '|' . $row['date'] . '|' . $row['period']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td><?php echo htmlspecialchars($row['period']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6">No absent attendance records found.</td>
                        </tr>
                    <?php } ?>
                </table>
                <button type="submit">Convert Selected to OD</button>
            </form>
        </section>

        <section class="dashboard-grid">
            <div class="panel">
                <h2>Latest Achievements</h2>
                <?php if ($achievements && $achievements->num_rows > 0) { ?>
                    <?php while ($row = $achievements->fetch_assoc()) { ?>
                        <div class="mini-card">
                            <?php if (!empty($row['image_path'])) { ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="">
                            <?php } ?>
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><?php echo htmlspecialchars($row['description']); ?></p>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p>No achievements added yet.</p>
                <?php } ?>
            </div>

            <div class="panel">
                <h2>Upcoming Events</h2>
                <?php if ($events && $events->num_rows > 0) { ?>
                    <?php while ($row = $events->fetch_assoc()) { ?>
                        <div class="mini-card">
                            <?php if (!empty($row['image_path'])) { ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="">
                            <?php } ?>
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><?php echo htmlspecialchars($row['event_date']); ?></p>
                            <p><?php echo htmlspecialchars($row['description']); ?></p>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p>No events added yet.</p>
                <?php } ?>
            </div>
        </section>
    </main>
</body>
</html>
