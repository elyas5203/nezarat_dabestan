<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

// Check if user is logged in and has permission
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('manage_forms');

$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$err = $success_msg = "";
$form_details = null;
$form_fields = [];

if ($form_id <= 0) {
    header("location: manage_dynamic_forms.php");
    exit;
}

// Fetch form details
$sql_form = "SELECT * FROM forms WHERE id = ?";
if ($stmt_form = mysqli_prepare($link, $sql_form)) {
    mysqli_stmt_bind_param($stmt_form, "i", $form_id);
    mysqli_stmt_execute($stmt_form);
    $result_form = mysqli_stmt_get_result($stmt_form);
    if (mysqli_num_rows($result_form) == 1) {
        $form_details = mysqli_fetch_assoc($result_form);
    } else {
        // Form not found
        header("location: manage_dynamic_forms.php");
        exit;
    }
    mysqli_stmt_close($stmt_form);
}

// Fetch form fields
$sql_fields = "SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_order ASC";
if ($stmt_fields = mysqli_prepare($link, $sql_fields)) {
    mysqli_stmt_bind_param($stmt_fields, "i", $form_id);
    mysqli_stmt_execute($stmt_fields);
    $result_fields = mysqli_stmt_get_result($stmt_fields);
    $form_fields = mysqli_fetch_all($result_fields, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_fields);
}

// Handle form submission for updating fields
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Begin transaction for atomicity
    mysqli_begin_transaction($link);

    try {
        // First, remove all existing fields for this form
        $sql_delete = "DELETE FROM form_fields WHERE form_id = ?";
        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt_delete, "i", $form_id);
            mysqli_stmt_execute($stmt_delete);
            mysqli_stmt_close($stmt_delete);
        } else {
            throw new Exception("Error preparing to delete old fields.");
        }

        // Now, insert all the submitted fields
        $sql_insert = "INSERT INTO form_fields (form_id, field_label, field_type, field_options, is_required, field_order) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
            foreach ($_POST['fields'] as $order => $field) {
                $label = $field['label'];
                $type = $field['type'];
                $options = ($type === 'select' || $type === 'radio' || $type === 'checkbox') ? $field['options'] : '';
                $required = isset($field['required']) ? 1 : 0;

                if (!empty($label) && !empty($type)) {
                    mysqli_stmt_bind_param($stmt_insert, "isssii", $form_id, $label, $type, $options, $required, $order);
                    if (!mysqli_stmt_execute($stmt_insert)) {
                        throw new Exception("Error inserting new field: " . mysqli_stmt_error($stmt_insert));
                    }
                }
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            throw new Exception("Error preparing to insert new fields.");
        }

        // If everything is fine, commit the transaction
        mysqli_commit($link);
        $success_msg = "فرم با موفقیت به‌روزرسانی شد.";
        // Refresh fields from DB
        if ($stmt_fields = mysqli_prepare($link, $sql_fields)) {
            mysqli_stmt_bind_param($stmt_fields, "i", $form_id);
            mysqli_stmt_execute($stmt_fields);
            $result_fields = mysqli_stmt_get_result($stmt_fields);
            $form_fields = mysqli_fetch_all($result_fields, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt_fields);
        }

    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        mysqli_rollback($link);
        $err = "خطا در به‌روزرسانی فرم: " . $e->getMessage();
    }
}

require_once "../includes/header.php";
?>
<style>
    .field-editor {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        background-color: #f9f9f9;
        position: relative;
    }
    .field-editor .handle {
        cursor: move;
        position: absolute;
        top: 10px;
        right: 10px;
        color: #aaa;
    }
    .options-container {
        display: none;
        margin-top: 10px;
    }
</style>

<div class="page-content">
    <h2>ویرایش فرم: <?php echo htmlspecialchars($form_details['form_name']); ?></h2>
    <p><?php echo htmlspecialchars($form_details['form_description']); ?></p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form action="edit_form.php?id=<?php echo $form_id; ?>" method="post" id="form-builder">
        <div id="fields-container">
            <?php foreach ($form_fields as $index => $field): ?>
                <div class="field-editor" data-index="<?php echo $index; ?>">
                    <i class="handle" data-feather="move"></i>
                    <div class="row">
                        <div class="col-md-4">
                            <label>عنوان سوال</label>
                            <input type="text" name="fields[<?php echo $index; ?>][label]" class="form-control" value="<?php echo htmlspecialchars($field['field_label']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label>نوع سوال</label>
                            <select name="fields[<?php echo $index; ?>][type]" class="form-control field-type-select">
                                <option value="text" <?php if($field['field_type'] == 'text') echo 'selected'; ?>>متن کوتاه</option>
                                <option value="textarea" <?php if($field['field_type'] == 'textarea') echo 'selected'; ?>>متن بلند</option>
                                <option value="number" <?php if($field['field_type'] == 'number') echo 'selected'; ?>>عدد</option>
                                <option value="select" <?php if($field['field_type'] == 'select') echo 'selected'; ?>>لیست کشویی</option>
                                <option value="radio" <?php if($field['field_type'] == 'radio') echo 'selected'; ?>>چند گزینه‌ای (یک انتخاب)</option>
                                <option value="checkbox" <?php if($field['field_type'] == 'checkbox') echo 'selected'; ?>>چند گزینه‌ای (چند انتخاب)</option>
                                <option value="date" <?php if($field['field_type'] == 'date') echo 'selected'; ?>>تاریخ</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <div class="options-container" style="<?php echo in_array($field['field_type'], ['select', 'radio', 'checkbox']) ? 'display:block;' : ''; ?>">
                                <label>گزینه‌ها (با کاما جدا کنید)</label>
                                <input type="text" name="fields[<?php echo $index; ?>][options]" class="form-control" value="<?php echo htmlspecialchars($field['field_options']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <input type="checkbox" name="fields[<?php echo $index; ?>][required]" <?php if($field['is_required']) echo 'checked'; ?>>
                            <label>الزامی</label>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-field">حذف سوال</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="add-field" class="btn btn-primary mt-3">افزودن سوال جدید</button>
        <hr>
        <button type="submit" class="btn btn-success">ذخیره تغییرات فرم</button>
        <a href="manage_dynamic_forms.php" class="btn btn-secondary">بازگشت</a>
    </form>
</div>

<!-- Field Template -->
<div id="field-template" style="display: none;">
    <div class="field-editor" data-index="__INDEX__">
        <i class="handle" data-feather="move"></i>
        <div class="row">
            <div class="col-md-4">
                <label>عنوان سوال</label>
                <input type="text" name="fields[__INDEX__][label]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label>نوع سوال</label>
                <select name="fields[__INDEX__][type]" class="form-control field-type-select">
                    <option value="text" selected>متن کوتاه</option>
                    <option value="textarea">متن بلند</option>
                    <option value="number">عدد</option>
                    <option value="select">لیست کشویی</option>
                    <option value="radio">چند گزینه‌ای (یک انتخاب)</option>
                    <option value="checkbox">چند گزینه‌ای (چند انتخاب)</option>
                    <option value="date">تاریخ</option>
                </select>
            </div>
            <div class="col-md-5">
                <div class="options-container">
                    <label>گزینه‌ها (با کاما جدا کنید)</label>
                    <input type="text" name="fields[__INDEX__][options]" class="form-control">
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-6">
                <input type="checkbox" name="fields[__INDEX__][required]">
                <label>الزامی</label>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-danger btn-sm remove-field">حذف سوال</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fieldsContainer = document.getElementById('fields-container');
    const addFieldBtn = document.getElementById('add-field');
    const fieldTemplate = document.getElementById('field-template').innerHTML;
    let fieldIndex = <?php echo count($form_fields); ?>;

    // Make fields sortable
    new Sortable(fieldsContainer, {
        animation: 150,
        handle: '.handle',
        onEnd: function() {
            // Re-index fields after sorting
            reindexFields();
        }
    });

    function reindexFields() {
        const editors = fieldsContainer.querySelectorAll('.field-editor');
        editors.forEach((editor, index) => {
            editor.dataset.index = index;
            const inputs = editor.querySelectorAll('[name]');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                const newName = name.replace(/\[\d+\]/, `[${index}]`);
                input.setAttribute('name', newName);
            });
        });
    }

    function attachFieldListeners(fieldElement) {
        // Type select change
        const typeSelect = fieldElement.querySelector('.field-type-select');
        const optionsContainer = fieldElement.querySelector('.options-container');
        typeSelect.addEventListener('change', function() {
            if (['select', 'radio', 'checkbox'].includes(this.value)) {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        });

        // Remove button
        const removeBtn = fieldElement.querySelector('.remove-field');
        removeBtn.addEventListener('click', function() {
            fieldElement.remove();
            reindexFields();
        });
    }

    // Add new field
    addFieldBtn.addEventListener('click', function() {
        const newFieldHtml = fieldTemplate.replace(/__INDEX__/g, fieldIndex);
        const newFieldElement = document.createElement('div');
        newFieldElement.innerHTML = newFieldHtml;

        const editor = newFieldElement.firstElementChild;
        fieldsContainer.appendChild(editor);
        feather.replace(); // To render the new move icon
        attachFieldListeners(editor);
        fieldIndex++;
        reindexFields();
    });

    // Attach listeners to existing fields
    document.querySelectorAll('.field-editor').forEach(attachFieldListeners);

});
</script>

<?php require_once "../includes/footer.php"; ?>
