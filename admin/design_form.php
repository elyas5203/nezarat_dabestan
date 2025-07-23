<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();
$form_id = $_GET['form_id'] ?? null;
$form_name = '';
$form_structure = '[]';

if ($form_id) {
    $stmt = mysqli_prepare($link, "SELECT form_name, form_structure FROM dynamic_forms WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $form_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($form = mysqli_fetch_assoc($result)) {
        $form_name = $form['form_name'];
        $form_structure = $form['form_structure'];
    }
    mysqli_stmt_close($stmt);
}

require_once "../includes/header.php";
?>
<style>
    .form-builder-container { display: flex; gap: 20px; }
    #form-builder { flex-grow: 1; min-height: 500px; padding: 20px; border: 1px dashed #ccc; border-radius: 5px; background: #f9f9f9; }
    .toolbox { width: 250px; }
    .toolbox .component { padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; cursor: grab; }
    .form-field { padding: 15px; background: #fff; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px; position: relative; }
    .form-field-actions { position: absolute; top: 5px; left: 5px; }
    .form-field-actions button { background: none; border: none; cursor: pointer; }
</style>

<div class="page-content">
    <h2>طراحی فرم - <span id="form-name-display"><?php echo htmlspecialchars($form_name ?: 'فرم جدید'); ?></span></h2>

    <form id="form-designer">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
        <div class="form-group">
            <label for="form_name">نام فرم:</label>
            <input type="text" id="form_name" name="form_name" class="form-control" value="<?php echo htmlspecialchars($form_name); ?>" required>
        </div>

        <div class="form-builder-container">
            <div class="toolbox">
                <h4>ابزارها</h4>
                <div class="component" data-type="text">فیلد متنی</div>
                <div class="component" data-type="textarea">کادر متنی</div>
                <div class="component" data-type="select">لیست کشویی</div>
                <div class="component" data-type="radio">گزینه‌های رادیویی</div>
                <div class="component" data-type="checkbox">چک‌باکس‌ها</div>
                <div class="component" data-type="date">تاریخ</div>
            </div>
            <div id="form-builder" class="dropzone">
                <!-- Dropped components will appear here -->
            </div>
        </div>

        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">ذخیره فرم</button>
        </div>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formBuilder = document.getElementById('form-builder');
    const toolbox = document.querySelector('.toolbox');
    const formDesigner = document.getElementById('form-designer');
    let formStructure = <?php echo $form_structure; ?>;

    // Initialize Sortable for the toolbox (for cloning)
    new Sortable(toolbox, {
        group: {
            name: 'shared',
            pull: 'clone',
            put: false
        },
        sort: false
    });

    // Initialize Sortable for the form builder area
    const sortable = new Sortable(formBuilder, {
        group: 'shared',
        animation: 150,
        onAdd: function (evt) {
            const type = evt.item.dataset.type;
            const newField = createFieldElement(type);
            evt.item.replaceWith(newField);
            updateStructure();
        }
    });

    // Function to create a form field element
    function createFieldElement(type, options = {}) {
        const fieldWrapper = document.createElement('div');
        fieldWrapper.className = 'form-field';
        fieldWrapper.dataset.type = type;

        const label = prompt("برچسب فیلد را وارد کنید:", options.label || "برچسب");
        fieldWrapper.dataset.label = label;

        let fieldHTML = `<strong>${label}</strong>`;
        // Add more complex fields here later (e.g., options for select/radio)

        fieldWrapper.innerHTML = `
            <div class="form-field-actions">
                <button type="button" class="delete-field"><i data-feather="trash-2"></i></button>
            </div>
            ${fieldHTML}
        `;
        feather.replace();
        return fieldWrapper;
    }

    // Function to render the form from the structure
    function renderForm() {
        formBuilder.innerHTML = '';
        formStructure.forEach(fieldData => {
            const fieldElement = createFieldElement(fieldData.type, fieldData);
            formBuilder.appendChild(fieldElement);
        });
    }

    // Function to update the JSON structure from the DOM
    function updateStructure() {
        formStructure = [];
        formBuilder.querySelectorAll('.form-field').forEach(field => {
            formStructure.push({
                type: field.dataset.type,
                label: field.dataset.label
                // Add other properties like name, options, etc.
            });
        });
    }

    // Handle deleting a field
    formBuilder.addEventListener('click', function(e) {
        if (e.target.closest('.delete-field')) {
            e.target.closest('.form-field').remove();
            updateStructure();
        }
    });

    // Handle form submission
    formDesigner.addEventListener('submit', function(e) {
        e.preventDefault();
        updateStructure();

        const formData = new FormData();
        formData.append('form_id', document.querySelector('[name=form_id]').value);
        formData.append('form_name', document.getElementById('form_name').value);
        formData.append('form_structure', JSON.stringify(formStructure));

        fetch('save_form.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'manage_dynamic_forms.php';
            } else {
                alert('خطا در ذخیره فرم: ' + data.error);
            }
        });
    });

    // Initial render
    renderForm();
});
</script>
<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
