<?php
require_once 'config.php';
include 'includes/header.php';

// --- 1. ACCESS CONTROL ---
// Ensure user is logged in AND is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='p-6 max-w-xl mx-auto mt-12 bg-rose-50 text-rose-700 border border-rose-100 rounded-2xl font-medium text-center shadow-sm'>
            Access Denied. You do not have permission to view this administrative resource.
          </div>";
    include 'includes/footer.php';
    exit;
}

$success_msg = '';
$error_msg = '';

// --- 2. HANDLE ACTIONS (POST/GET) ---

// CREATE User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (!empty($username) && !empty($email) && !empty($password) && in_array($role, ['admin', 'staff'])) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password, $role]);
            $success_msg = "New account for '$username' successfully generated.";
        } catch (PDOException $e) {
            $error_msg = "Error: Username or Email string already exists in records.";
        }
    } else {
        $error_msg = "Please satisfy all mandatory user input requirements.";
    }
}

// UPDATE User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($email) && in_array($role, ['admin', 'staff'])) {
        try {
            if (!empty($password)) {
                // If a new password was typed, update it with encryption
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $email, $hashed_password, $role, $id]);
            } else {
                // Otherwise, leave the existing password untouched
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $email, $role, $id]);
            }
            
            // Instantly sync layout greeting variables if admin modifies their own record
            if ($id == $_SESSION['user_id']) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
            }

            $success_msg = "User configurations updated successfully!";
        } catch (PDOException $e) {
            $error_msg = "Error: Profile updating conflicts with existing email or username handles.";
        }
    } else {
        $error_msg = "Validation check failed.";
    }
}

// DELETE User
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Self-deletion blocker protection guardrail logic
    if ($delete_id === intval($_SESSION['user_id'])) {
        $error_msg = "Security Protection Action: System cannot execute self-deletion routines on active sessions.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $success_msg = "User record permanently dropped from registration pools.";
        } catch (PDOException $e) {
            $error_msg = "Cannot delete this user. They might be linked to historical log audit transactions.";
        }
    }
}

// --- 3. DATA REQUISITIONS FETCH ---
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}

$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY username ASC")->fetchAll();
?>

<!-- --- 4. DATA PRESENTATION UI --- -->
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">User Account Matrix</h1>
        <p class="text-sm text-gray-500">Provision authorization, reset staff profiles, and balance administrative access scopes.</p>
    </div>

    <!-- Message Alerts Box UI components -->
    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 text-emerald-700 text-sm p-4 rounded-xl border border-emerald-100 font-medium"><?= $success_msg; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="bg-rose-50 text-rose-700 text-sm p-4 rounded-xl border border-rose-100 font-medium"><?= $error_msg; ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- LEFT COLUMN SIDE FORM PANEL: Context-aware Add / Edit User Form Layout Component -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
            <?php if ($edit_user): ?>
                <h3 class="text-lg font-bold text-gray-900">Modify System Account Profile</h3>
                <form action="users.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $edit_user['id']; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username Code</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($edit_user['username']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email Address Handle</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Access Scope Role Authority Level</label>
                        <select name="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-indigo-500">
                            <option value="staff" <?= $edit_user['role'] === 'staff' ? 'selected' : ''; ?>>Staff Access</option>
                            <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : ''; ?>>Administrative Access</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reset System Password</label>
                        <input type="password" name="password" placeholder="Leave empty to preserve existing keys" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 py-2 px-4 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">Save Profiles</button>
                        <a href="users.php" class="py-2 px-4 text-sm font-semibold rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 text-center transition">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <h3 class="text-lg font-bold text-gray-900">Provision New Operator</h3>
                <form action="users.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username Identification</label>
                        <input type="text" name="username" required placeholder="e.g., alex_operator" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email Address Handle</label>
                        <input type="email" name="email" required placeholder="e.g., employee@firm.com" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Assign Authority System Role Level</label>
                        <select name="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-indigo-500">
                            <option value="staff" selected>Staff Access (Logs Movements & Catalogs)</option>
                            <option value="admin">Administrative Access (Complete Overrides)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Security Access Key Password</label>
                        <input type="password" name="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    </div>
                    
                    <button type="submit" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm pt-2">Generate Operator Profile</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN SIDE FORM PANEL: Existing Users Registry Data Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Identities Profile</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">System Role Scope Authority</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Creation Records Timestamp</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-gray-900"><?= htmlspecialchars($u['username']); ?></div>
                                <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($u['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase tracking-wide">Admin</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-slate-50 text-slate-600 border border-slate-100 uppercase tracking-wide">Staff</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">
                                <?= date('Y-m-d H:i', strtotime($u['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                <a href="users.php?edit=<?= $u['id']; ?>" class="text-indigo-600 hover:text-indigo-900 transition">Edit</a>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?= $u['id']; ?>" onclick="return confirm('Drop this credential profile entry permanently from internal records databases?');" class="text-rose-600 hover:text-rose-900 transition">Delete</a>
                                <?php else: ?>
                                    <span class="text-xs font-medium text-gray-300 italic cursor-not-allowed" title="Self deletion locked.">Active Self</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>