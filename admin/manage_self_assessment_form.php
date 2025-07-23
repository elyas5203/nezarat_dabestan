<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !has_permission('manage_forms')) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
// For this dynamic form, we will create a new form record. Let's assume its ID is 2.
const DYNAMIC_SELF_ASSESSMENT_FORM_ID = 2;

$err = $success_msg = "";

// --- Handle Form Field CUD Operations ---
// (CUD: Create, Update, Delete)

// Add/Update Field
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_field'])) {
    $field_id = $_POST['field_id'] ?? null;
    $field_label = trim($_POST['field_label']);
    $field_type = $_POST['field_type'];
    $field_options = trim($_POST['field_options']);
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    $field_order = (int)($_POST['field_order']);

    if (empty($field_label) || empty($field_type)) {
        $err = "نام فیلد و نوع آن الزامی است.";
    } else {
        if ($field_id) { // Update existing field
            $sql = "UPDATE form_fields SET field_label = ?, field_type = ?, field_options = ?, is_required = ?, field_order = ? WHERE id = ? AND form_id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "sssiiii", $field_label, $field_type, $field_options, $is_required, $field_order, $field_id, DYNAMIC_SELF_ASSESSMENT_FORM_ID);
        } else { // Insert new field
            $sql = "INSERT INTO form_fields (form_id, field_label, field_type, field_options, is_required, field_order) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "isssii", DYNAMIC_SELF_ASSESSMENT_FORM_ID, $field_label, $field_type, $field_options, $is_required, $field_order);
        }

        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "فیلد با موفقیت ذخیره شد.";
        } else {
            $err = "خطا در ذخیره فیلد: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// Delete Field
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $field_id_to_delete = $_GET['id'];
    $sql = "DELETE FROM form_fields WHERE id = ? AND form_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $field_id_to_delete, DYNAMIC_SELF_ASSESSMENT_FORM_ID);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "فیلد با موفقیت حذف شد.";
    } else {
        $err = "خطا در حذف فیلد.";
    }
    mysqli_stmt_close($stmt);
}


// Fetch all fields for the dynamic form
$fields_query = mysqli_query($link, "SELECT * FROM form_fields WHERE form_id = " . DYNAMIC_SELF_ASSESSMENT_FORM_ID . " ORDER BY field_order ASC");
$fields = mysqli_fetch_all($fields_query, MYSQLI_ASSOC);

require_once "../includes/header.php";
?>
<style>
    .form-builder-container { max-width: 900px; margin: auto; }
    .field-item { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
    .field-item .field-info { font-weight: bold; }
    .field-item .field-type { color: #666; font-size: 0.9em; }
    .add-field-btn { display: block; width: 100%; padding: 15px; background: var(--success-color); color: white; border: none; border-radius: 8px; font-size: 1.1em; cursor: pointer; margin-top: 20px; }
    #field-modal { /* Basic modal styles */ }
</style>

<div class="page-content">
    <h2>مدیریت سوالات فرم خوداظهاری</h2>
    <p>در این بخش می‌توانید سوالات فرم خوداظهاری را اضافه، ویرایش یا حذف کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-builder-container">
        <div id="fields-list">
            <?php if (empty($fields)): ?>
                <div class="alert alert-info">هنوز هیچ سوالی برای این فرم تعریف نشده است.</div>
            <?php else: ?>
                <?php foreach ($fields as $field): ?>
                    <div class="field-item">
                        <div class="field-details">
                            <span class="field-info"><?php echo htmlspecialchars($field['field_label']); ?> <?php if($field['is_required']) echo '<span style="color:red;">*</span>'; ?></span>
                            <small class="field-type">(نوع: <?php echo $field['field_type']; ?>)</small>
                        </div>
                        <div class="field-actions">
                            <button class="btn btn-warning btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($field)); ?>)">ویرایش</button>
                            <a href="?action=delete&id=<?php echo $field['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این سوال مطمئن هستید؟')">حذف</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" onclick="openEditModal()">+ افزودن سوال جدید</button>
    </div>
</div>

<!-- Modal for Add/Edit Field -->
<div id="field-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>افزودن/ویرایش سوال</h3>
        <form id="field-form" method="post" action="">
            <input type="hidden" name="field_id" id="field_id">
            <div class="form-group">
                <label for="field_label">متن سوال (Label):</label>
                <input type="text" name="field_label" id="field_label" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="field_type">نوع سوال:</label>
                <select name="field_type" id="field_type" class="form-control" required>
                    <option value="text">متن کوتاه (Text)</option>
                    <option value="textarea">متن بلند (Textarea)</option>
                    <option value="number">عددی (Number)</option>
                    <option value="select">لیست کشویی (Select)</option>
                    <option value="radio">گزینه رادیویی (Radio)</option>
                    <option value="checkbox">چک‌باکس (Checkbox)</option>
                    <option value="date">تاریخ (Date)</option>
                </select>
            </div>
            <div class="form-group" id="options-group" style="display:none;">
                <label for="field_options">گزینه‌ها (با کاما جدا کنید):</label>
                <input type="text" name="field_options" id="field_options" class="form-control">
            </div>
             <div class="form-group">
                <label for="field_order">ترتیب نمایش:</label>
                <input type="number" name="field_order" id="field_order" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_required" id="is_required" value="1">
                    این فیلد الزامی است.
                </label>
            </div>
            <div class="form-group">
                <button type="submit" name="save_field" class="btn btn-success">ذخیره</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('field-modal');
    const form = document.getElementById('field-form');
    const optionsGroup = document.getElementById('options-group');
    const fieldTypeSelect = document.getElementById('field_type');

    function openEditModal(field = null) {
        form.reset();
        if (field) {
            document.getElementById('field_id').value = field.id;
            document.getElementById('field_label').value = field.field_label;
            document.getElementById('field_type').value = field.field_type;
            document.getElementById('field_options').value = field.field_options;
            document.getElementById('field_order').value = field.field_order;
            document.getElementById('is_required').checked = field.is_required == 1;
        }
        toggleOptionsVisibility();
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function toggleOptionsVisibility() {
        const type = fieldTypeSelect.value;
        if (type === 'select' || type === 'radio' || type === 'checkbox') {
            optionsGroup.style.display = 'block';
        } else {
            optionsGroup.style.display = 'none';
        }
    }

    fieldTypeSelect.addEventListener('change', toggleOptionsVisibility);
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php
require_once "../includes/footer.php";
?>
