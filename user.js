// --- Global Constants ---
const WEB_APP_URL = 'https://script.google.com/macros/s/AKfycby2A79FzX-kgKH04lZki5AGOD8DCvN8KsbU8E3CDm9jddeci48w0542qA-AwLV7y4opVA/exec'; // URL وب اپ شما

// --- DOM Elements (User Specific) ---
const loginSection = document.getElementById('loginSection');
const userFormSection = document.getElementById('userFormSection');
const registeredUserDashboardSection = document.getElementById('registeredUserDashboardSection');
const registeredUserMainContent = document.getElementById('registeredUserMainContent');
const changePasswordOwnFormContainer = document.getElementById('changePasswordOwnFormContainer');
const changePasswordOwnMessageArea = document.getElementById('changePasswordOwnMessageArea');
const assignedVisitsListDiv = document.getElementById('assignedVisitsList');

const loginMessageArea = document.getElementById('loginMessageArea');
const userFormMessageArea = document.getElementById('userFormMessageArea');
const userWelcomeMessageEl = document.getElementById('userWelcomeMessage');
const registeredUserWelcomeMessageEl = document.getElementById('registeredUserWelcomeMessage');
const formNavigationEl = document.getElementById('formNavigation'); // This is form-navigation-viewer in HTML
const formSectionsContainerEl = document.getElementById('formSectionsContainer');
const downloadUserFormPdfButton = document.getElementById('downloadUserFormPdfButton');


// --- Form Structure (باید با Code.gs و admin.js یکسان باشد) ---
const formStructure = [
    { id: 'class_stats', name: 'آمار کلاس', fields: [ { id: 'cs_total_students', label: 'تعداد کل دانش آموزان', type: 'number', required: true, placeholder: 'عدد وارد کنید' },{ id: 'cs_present_this_week', label: 'تعداد حاضرین این هفته', type: 'number', required: true, placeholder: 'عدد وارد کنید' },{ id: 'cs_absent_this_week', label: 'تعداد غائبین این هفته', type: 'number', required: true, placeholder: 'عدد وارد کنید' },{ id: 'cs_irregular_attendance', label: 'تعداد متربیانی که حضور منظم ندارند', type: 'number', required: false, placeholder: 'عدد (اختیاری)' },{ id: 'cs_latecomers_students', label: 'تعداد متأخرین (دانش‌آموزان)', type: 'number', required: false, placeholder: 'عدد (اختیاری)' },{ id: 'cs_teacher_attendance_status', label: 'وضعیت حضور مدرسین', type: 'checkbox', options: ['مدرس اول (راه انداز)', 'مدرس دوم (کمک)', 'مدرس سوم'], required: true },{ id: 'cs_teacher1_delay_minutes', label: 'میزان دقیقه تاخیر مدرس اول (در صورت حضور)', type: 'number', required: false, placeholder: 'عدد (دقایق)', dependsOn: 'cs_teacher_attendance_status', dependsValue: 'مدرس اول (راه انداز)' },{ id: 'cs_teacher2_delay_minutes', label: 'میزان دقیقه تاخیر مدرس دوم (در صورت حضور)', type: 'number', required: false, placeholder: 'عدد (دقایق)', dependsOn: 'cs_teacher_attendance_status', dependsValue: 'مدرس دوم (کمک)' },{ id: 'cs_teacher3_delay_minutes', label: 'میزان دقیقه تاخیر مدرس سوم (در صورت حضور)', type: 'number', required: false, placeholder: 'عدد (اختیاری)', dependsOn: 'cs_teacher_attendance_status', dependsValue: 'مدرس سوم' } ] },
    { id: 'skill_start_class', name: 'مهارت شروع کلاس', fields: [ { id: 'ssc_focus_creation', label: 'سامان دادن به افکار و ایجاد تمرکز', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ssc_attendance_method', label: 'شیوه انجام حضور و غیاب', type: 'checkbox', options: ['هوشمند', 'کتبی', 'شفاهی', 'عدم انجام'], required: true },{ id: 'ssc_attendance_list_shown', label: 'آیا لیست حضور و غیاب به نظر فراگیران رسید؟', type: 'radio', options: ['بله', 'خیر'], required: true },{ id: 'ssc_prev_week_review', label: 'آیا درباره مطالب هفته گذشته پرسش شد؟', type: 'radio', options: ['بله', 'خیر'], required: true },{ id: 'ssc_mind_prep_new_lesson', label: 'آماده سازی ذهن برای درس جدید', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ssc_motivation_creation', label: 'ایجاد علاقه و انگیزه برای مطلب جدید', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ssc_reaction_abs_delay_hw', label: 'واکنش به غیبت،تاخیر،انجام و عدم انجام تکالیف', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true } ] },
    { id: 'skill_alt_teaching', name: 'مهارت تدریس به گونه‌ای دیگر', fields: [ { id: 'sat_lesson_plan_exists', label: 'به نظر شما مدرس قبل از ورود به کلاس طرح درس تدوین کرده بود؟', type: 'radio', options: ['بله', 'خیر'], required: true },{ id: 'sat_student_participation_learning', label: 'مشارکت دانش آموزان به همراه معلم در فرایند پویای یادگیری', type: 'select', options: ['', 'کاملا', 'تا حدی', 'کم', 'اصلا'], required: true },{ id: 'sat_teaching_method_percentage', label: 'نحوه آموزش و برگزاری کلاس بدون درنظر گرفتن بخش بازی', type: 'percentage_rows', rows: [ { id: 'sat_group_based_percent', label: 'گروه محور' }, { id: 'sat_student_based_percent', label: 'فراگیر محور' },{ id: 'sat_tech_based_percent', label: 'تکنولوژی محور' }, { id: 'sat_teacher_based_percent', label: 'مدرس محور' } ], options: ['0', '20', '40', '60', '80', '100'], required: true },{ id: 'sat_modern_teaching_methods', label: 'استفاده از روش های تدریس مدرن', type: 'checkbox_with_other', options: ['ایفای نقش', 'ابزار تدریس', 'بازی های آموزشی', 'نداشتن'], otherOptionLabel: 'سایر (توضیح دهید)', required: true },{ id: 'sat_quran_recitation_method', label: 'روش قرائت قرآن در کلاس', type: 'checkbox', options: ['زنده', 'چندرسانه‌ای', 'همخوانی', 'تک خوانی', 'نداشتن'], required: true } ] },
    { id: 'skill_storytelling', name: 'مهارت استفاده از فنون قصه‌گویی', fields: [ { id: 'sst_voice_tone_appropriateness', label: 'آیا صدا و لحن مدرس، متناسب قصه گویی است؟', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'sst_atmosphere_creation', label: 'فضاسازی مناسب و قابل درک برای فراگیران', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'sst_story_suitability', label: 'آیا قصه، متناسب با زمان، فرهنگ، و سن مخاطبین انتخاب گردیده بود؟', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'sst_storytelling_techniques', label: 'استفاده از شیوه ها و فنون قصه گویی', type: 'checkbox_with_other', options: ['قصه گویی در قالب نمایش', 'قصه گویی با تقلید صدا', 'قصه گویی ساده', 'قصه خوانی', 'پرده خوانی', 'قصه نداشتن'], otherOptionLabel: 'دیگر (توضیح دهید)', required: true } ] },
    { id: 'skill_class_control', name: 'مهارت کنترل کلاس و انضباط', fields: [ { id: 'scc_general_discipline', label: 'نظم و انضباط عمومی کلاس', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'scc_behavior_guidance', label: 'هدایت و راهنمایی مناسب رفتار ها و گفتارهای فراگیران', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'scc_teacher_gaze_distribution', label: 'تقسیم نگاه معلم بر متربیان', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'scc_discipline_methods_voice_actions', label: 'استفاده از صدا و بیان، حرکات و روش های به کار گرفته شده از سوی معلم در جهت برقراری انضباط', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'scc_punishment_methods_desc', label: 'روش های تنبیهی (توضیح دهید)', type: 'textarea', required: true, placeholder: 'روش‌های مشاهده شده را شرح دهید...' } ] },
    { id: 'skill_imam_mahdi_reminder', name: 'مهارت تذکر به امام عصر(عج)', fields: [ { id: 'simr_attention_to_imam', label: 'توجه داشتن به امام عصر(علیه السلام) در فضای کلی تدریس', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'simr_encouragement_love_service', label: 'تشویق به محبت، ایجاد ارتباط و خدمتگزاری به امام حیّ با تاکید بر ناظر بودن ایشان', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'simr_politeness_during_mention', label: 'ادب فراگیران در دعای اول جلسه یا موقع نام بردن از آن حضرت', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'simr_advice_proximity_sins', label: 'توصیه ی کارهایی که باعث نزدیکی به آن حضرت میشود و نهی انجام گاناهان که باعث جدایی از یاد آن حضرت است', type: 'radio', options: ['بله', 'خیر'], required: true },{ id: 'simr_mahdaviat_topic_title', label: 'بیان موضوع خاصی از مهدویت؛ لطفا فقط عنوان را ذکر نمایید', type: 'text', required: true, placeholder: 'عنوان موضوع مهدوی مطرح شده' } ] },
    { id: 'content_evaluation', name: 'محتوا', fields: [ { id: 'ce_teacher_mastery', label: 'میزان تسلط مدرسین به مباحث', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ce_expression_comprehension', label: 'نحوه بیان و تفهیم مطالب', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ce_content_curriculum_match', label: 'میزان تطبیق با محتوای آموزشی', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ce_teaching_aids_usage', label: 'میزان استفاده از وسائل کمک آموزشی', type: 'select', options: ['', 'به اندازه', 'زیاد', 'کم', 'اصلا'], required: true },{ id: 'ce_teacher_student_language', label: 'نحوه بیان مدرس در ارتباط با متربی', type: 'radio', options: ['استفاده از زبان متربی', 'بیانی فراتر از فهم کودک'], required: true },{ id: 'ce_teacher_question_response', label: 'نحوه برخورد مدرس در مواجهه با پرسش', type: 'checkbox', options: ['مناسب', 'ارجاع به آینده', 'پاسخ کامل', 'عدم پاسخ'], required: true },{ id: 'ce_real_life_examples_religious', label: 'استفاده از مثال های روز و ملموس برای بیان آموزه های دینی - اعتقادی', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ce_class_sequence_mention_game_snack', label: 'سین کلاس(به ترتیب انجام) بازی و پذیرایی هم ذکر شود', type: 'textarea', required: true, placeholder: 'ترتیب فعالیت‌های کلاس را شرح دهید...' } ] },
    { id: 'game_and_refreshments', name: 'بازی و پذیرایی', fields: [ { id: 'gr_game_name', label: 'نام بازی', type: 'text', required: true, placeholder: 'نام بازی انجام شده' },{ id: 'gr_game_duration_minutes', label: 'مدت زمان بازی (دقیقه)', type: 'number', required: true, placeholder: 'به دقیقه وارد کنید' },{ id: 'gr_game_type', label: 'نوع بازی', type: 'checkbox_with_other', options: ['تحرکی', 'تمرکزی', 'آموزشی', 'آپارتمانی', 'حیاطی', 'با ابزار و وسیله', 'بدون ابزار و وسیله', 'گروه‌های جداگانه', 'فردی', 'جمعی همه باهم'], otherOptionLabel: 'دیگر (توضیح دهید)', required: true },{ id: 'gr_student_interest_game', label: 'میزان علاقه فراگیران به بازی', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'gr_student_interaction', label: 'رابطه متقابل فراگیران', type: 'select', options: ['', 'عالی', 'خوب', 'متوسط', 'نامناسب'], required: true },{ id: 'gr_student_teacher_interaction', label: 'رابطه فراگیران با مدرسین', type: 'select', options: ['', 'عالی', 'خوب', 'متوسط', 'نامناسب'], required: true },{ id: 'gr_general_moral_traits_students', label: 'ویژگی های بارز اخلاقی عمومی فراگیران', type: 'textarea', required: true, placeholder: 'ویژگی‌های مشاهده شده را شرح دهید...' } ]},
    { id: 'general_environment', name: 'محیط کلاس', fields: [ { id: 'ge_hvac_ventilation', label: 'سیستم سرمایش و گرمایشی و تهویه مناسب', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ge_lighting', label: 'نورگیری مناسب فضای کلاس', type: 'select_score', options: ['', '1 (خیلی ضعیف)', '2 (ضعیف)', '3 (خوب)', '4 (عالی)'], required: true },{ id: 'ge_space_to_student_ratio', label: 'متناسب بودن فضای کلاس نسبت به تعداد فراگیران (ابعاد)', type: 'select', options: ['', 'بزرگ', 'متناسب', 'کمبود فضا'], required: true },{ id: 'ge_restroom_accessibility', label: 'محل سرویس بهداشتی', type: 'select', options: ['', 'در دسترس', 'دسترسی سخت', 'عدم دسترسی'], required: true },{ id: 'ge_seating_type', label: 'استفاده از صندلی', type: 'checkbox', options: ['بله', 'خیر', 'نیاز است', 'نیاز نیست'], required: true } ]},
    { id: 'general_facilities', name: 'امکانات', fields: [ { id: 'gf_whiteboard', label: 'تخت وایت برد', type: 'checkbox', options: ['متناسب', 'نامتناسب', 'کمبود', 'ناموجود'], required: true },{ id: 'gf_board_stand', label: 'پایه تخته', type: 'checkbox', options: ['متناسب', 'نامتناسب', 'کمبود', 'ناموجود'], required: true },{ id: 'gf_projector_tv', label: 'پرژکتور یا تلوزیون', type: 'checkbox', options: ['متناسب', 'نامتناسب', 'کمبود', 'ناموجود'], required: true },{ id: 'gf_speaker_system', label: 'اسپیکر یا باند', type: 'checkbox', options: ['متناسب', 'نامتناسب', 'کمبود', 'ناموجود'], required: true } ]}
];


// --- Helper Functions (User-specific or shared) ---
function displayMessage(areaElement, message, isSuccess = true) {
    if (areaElement) {
        areaElement.innerHTML = message;
        areaElement.className = 'messageArea ' + (isSuccess ? 'message-success' : 'message-error');
        areaElement.classList.remove('hidden');
        setTimeout(() => {
            if (areaElement) { // Check again as element might be gone
                areaElement.classList.add('hidden');
                areaElement.innerHTML = '';
            }
        }, 7000);
    } else {
        console.warn("User displayMessage: Message area not found for:", message);
    }
}

async function callAppsScriptUser(action, payload = {}) { // Renamed to avoid conflict if merged later
    let currentMessageAreaForCall = loginMessageArea; // Default for user pages

    if (userFormSection && !userFormSection.classList.contains('hidden')) {
        currentMessageAreaForCall = userFormMessageArea;
    } else if (registeredUserDashboardSection && !registeredUserDashboardSection.classList.contains('hidden')) {
        if (changePasswordOwnFormContainer && !changePasswordOwnFormContainer.classList.contains('hidden')) {
            currentMessageAreaForCall = changePasswordOwnMessageArea;
        } else {
             // For general messages in registered user dashboard, can use loginMessageArea or a dedicated one
             // For now, using loginMessageArea for simplicity if no specific area in dashboard shown
        }
    }

    if (!WEB_APP_URL || WEB_APP_URL === 'YOUR_WEB_APP_URL_HERE' || !WEB_APP_URL.startsWith('https://script.google.com/macros/s/')) {
        if(currentMessageAreaForCall) displayMessage(currentMessageAreaForCall, 'خطا: URL وب اپلیکیشن به درستی تنظیم نشده است!', false);
        console.error('WEB_APP_URL is not configured correctly for user script.');
        return { status: 'error', message: 'Web App URL not configured.' };
    }
    if(currentMessageAreaForCall) {
        currentMessageAreaForCall.classList.add('hidden');
        currentMessageAreaForCall.innerHTML = '';
    }


    const params = new URLSearchParams({action, ...payload});
    try {
        const response = await fetch(WEB_APP_URL, { method: 'POST', body: params });
        if (!response.ok) {
            const errorText = await response.text();
            console.error("Network error response text for action " + action + ":", errorText);
            throw new Error(`خطای شبکه: ${response.status} ${response.statusText}`);
        }
        const result = await response.json();
        // Log successful backend responses for debugging
        // console.log(`User Response for action ${action}:`, result); 

         if (result.status === 'error') {
             const passThroughErrorActions = ['validateRegisteredUserLogin', 'validateUserAccess', 'validateAssignedVisitAccess', 'changeOwnPassword'];
             if (!passThroughErrorActions.includes(action) || (result.data && result.data.error)) {
                throw new Error(result.message || 'خطای نامشخص از سمت سرور Apps Script');
             }
        }
        return result;
    } catch (error) {
        console.error('Error in callAppsScriptUser for action "' + action + '":', error);
        if(currentMessageAreaForCall) displayMessage(currentMessageAreaForCall, 'خطا در ارتباط با سرور: ' + error.message, false);
        return { status: 'error', message: error.message, data: { error: true, message: error.message } };
    }
}

function togglePasswordVisibilityUser(inputId, buttonElement) {
    const passwordInput = document.getElementById(inputId);
    if (!passwordInput || !buttonElement) return;
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        buttonElement.textContent = "مخفی";
    } else {
        passwordInput.type = "password";
        buttonElement.textContent = "نمایش";
    }
}

// --- Show/Hide Main Page Sections ---
function showUserSection(sectionIdToShow) {
     if(loginSection) loginSection.classList.add('hidden');
     if(userFormSection) userFormSection.classList.add('hidden');
     if(registeredUserDashboardSection) registeredUserDashboardSection.classList.add('hidden');
     if(changePasswordOwnFormContainer) changePasswordOwnFormContainer.classList.add('hidden'); // Ensure this is also hidden

     const sectionElement = document.getElementById(sectionIdToShow);
     if (sectionElement) {
         sectionElement.classList.remove('hidden');
         if (sectionIdToShow === 'registeredUserDashboardSection' && registeredUserMainContent) {
             registeredUserMainContent.classList.remove('hidden'); // Ensure main dashboard content is visible
         }
     } else if (sectionIdToShow) { // Only log error if a specific section was requested but not found
         console.error("User section with ID '" + sectionIdToShow + "' not found.");
     }
}


// --- Login, Logout, Session Management for Users ---
function logout() {
    sessionStorage.clear(); // Clear all session data
    if(document.getElementById('loginIdentifier')) document.getElementById('loginIdentifier').value = '';
    if(document.getElementById('loginPassword')) document.getElementById('loginPassword').value = '';

    const messageAreasToClear = [loginMessageArea, userFormMessageArea, changePasswordOwnMessageArea];
    messageAreasToClear.forEach(area => {
        if (area) {
            area.classList.add('hidden');
            area.innerHTML = '';
        }
    });
    
    // Clear specific content areas
    if(assignedVisitsListDiv) assignedVisitsListDiv.innerHTML = '<p class="no-data-message">در حال بارگذاری بازدیدهای شما...</p>';
    if(formSectionsContainerEl) formSectionsContainerEl.innerHTML = '';
    if(formNavigationEl) formNavigationEl.innerHTML = '';
    if(downloadUserFormPdfButton) downloadUserFormPdfButton.style.display = 'none';


    showUserSection('loginSection');
}

async function handleUnifiedLogin() {
    const identifierInput = document.getElementById('loginIdentifier');
    const passwordInput = document.getElementById('loginPassword');
    if(!identifierInput || !passwordInput || !loginMessageArea) return;

    const identifier = identifierInput.value.trim();
    const password = passwordInput.value; // Password for users is sent as is to backend

    if (!identifier || !password) {
        displayMessage(loginMessageArea, 'لطفاً نام کاربری/کد کلاس و رمز عبور/پسورد بازدید را وارد کنید.', false);
        return;
    }
    displayMessage(loginMessageArea, 'در حال بررسی اطلاعات...', true);

    // Try registered user login first
    let resultRegisteredUser = await callAppsScriptUser('validateRegisteredUserLogin', { userId: identifier, password: password });
    console.log("Registered User Login Attempt:", resultRegisteredUser);

    if (resultRegisteredUser.status === 'success' && resultRegisteredUser.data && resultRegisteredUser.data.isValid === true) {
        sessionStorage.setItem('currentUserType', 'user');
        sessionStorage.setItem('loggedInUser', resultRegisteredUser.data.userId);
        sessionStorage.setItem('loggedInUserDisplayName', resultRegisteredUser.data.displayName);
        if (registeredUserWelcomeMessageEl) registeredUserWelcomeMessageEl.textContent = `خوش آمدید، ${resultRegisteredUser.data.displayName || resultRegisteredUser.data.userId}!`;
        
        showUserSection('registeredUserDashboardSection');
        loadAssignedVisits();
        displayMessage(loginMessageArea, resultRegisteredUser.data.message || 'ورود موفقیت آمیز بود.', true);
        identifierInput.value = ''; passwordInput.value = '';
        return;
    } else if (resultRegisteredUser.status === 'success' && resultRegisteredUser.data && resultRegisteredUser.data.isValid === false) {
        // If it's not "user not found", it means wrong password or inactive, so display message and stop.
        if (resultRegisteredUser.data.message && resultRegisteredUser.data.message !== "کاربری با این نام کاربری یافت نشد.") {
            displayMessage(loginMessageArea, resultRegisteredUser.data.message, false);
            return;
        }
        // Otherwise, it might be a class code, so continue to anonymous check.
    } else if (resultRegisteredUser.status === 'error') {
        // If there was a server error during registered user validation, show it and stop.
        displayMessage(loginMessageArea, resultRegisteredUser.message || 'خطا در اعتبارسنجی کاربر ثبت‌شده.', false);
        return;
    }


    // Try anonymous user login if registered user login failed with "user not found" or if identifier wasn't a username
    let resultAnonymousUser = await callAppsScriptUser('validateUserAccess', { classCode: identifier, visitPassword: password });
    console.log("Anonymous User Login Attempt:", resultAnonymousUser);

    if (resultAnonymousUser.status === 'success' && resultAnonymousUser.data && resultAnonymousUser.data.isValid === true) {
        sessionStorage.setItem('currentUserType', 'anonymous');
        sessionStorage.setItem('currentClassCode', resultAnonymousUser.data.classCode);
        sessionStorage.setItem('currentVisitPassword', String(resultAnonymousUser.data.visitPassword));
        sessionStorage.setItem('currentClassName', resultAnonymousUser.data.className);
        if(userWelcomeMessageEl) userWelcomeMessageEl.innerHTML = `فرم بازدید برای کلاس: <strong>${resultAnonymousUser.data.className}</strong> (کد: ${resultAnonymousUser.data.classCode}، شماره بازدید: ${resultAnonymousUser.data.visitPassword})`;
        
        showUserSection('userFormSection');
        loadUserFormStructure(formSectionsContainerEl, false, 'formNavigation'); // formNavigation is the ID in user's HTML
        if(downloadUserFormPdfButton) downloadUserFormPdfButton.style.display = 'inline-block'; // Show PDF button
        
        displayMessage(loginMessageArea, 'ورود با کد بازدید موفقیت آمیز بود. لطفاً فرم را تکمیل کنید.', true);
        identifierInput.value = ''; passwordInput.value = '';
    } else if (resultAnonymousUser.status === 'success' && resultAnonymousUser.data && resultAnonymousUser.data.isValid === false) {
        // If both registered and anonymous login failed with specific "invalid" messages
        displayMessage(loginMessageArea, resultAnonymousUser.data.message || 'کد کلاس یا پسورد بازدید نامعتبر است.', false);
    } else if (resultAnonymousUser.status === 'error'){
        displayMessage(loginMessageArea, resultAnonymousUser.message || 'خطا در اعتبارسنجی کد بازدید.', false);
    } else {
        // Generic failure if no specific validation outcome from either attempt but also no server error.
        // This usually means the initial registered user check returned "user not found" and anonymous also failed.
        displayMessage(loginMessageArea, 'نام کاربری/کد کلاس یا رمز عبور/پسورد بازدید نامعتبر است.', false);
    }
}


// --- Registered User Dashboard Functions ---
async function loadAssignedVisits() {
    const userId = sessionStorage.getItem('loggedInUser');
    if (!userId || !assignedVisitsListDiv) return;

    assignedVisitsListDiv.innerHTML = '<p class="no-data-message">در حال بارگذاری بازدیدهای شما...</p>';
    const result = await callAppsScriptUser('getAssignedVisitsForUser', { userId });
    console.log("Assigned visits for user:", result);

    if (result.status === 'success' && Array.isArray(result.data)) {
        if (result.data.length === 0) {
            assignedVisitsListDiv.innerHTML = '<p class="no-data-message">در حال حاضر هیچ بازدید تکمیل نشده‌ای به شما اختصاص داده نشده است.</p>';
        } else {
            assignedVisitsListDiv.innerHTML = ''; 
            result.data.forEach(visit => {
                const visitItem = document.createElement('div');
                visitItem.className = 'assigned-visit-item';
                visitItem.innerHTML = `
                    <p>
                        <strong>کلاس:</strong> ${visit.className} (کد: ${visit.classCode})<br>
                        <strong>شماره بازدید:</strong> ${visit.visitPassword}
                        <span>تاریخ ایجاد: ${visit.createdDate || 'نامشخص'}</span>
                    </p>
                    <button onclick="startAssignedVisit('${visit.classCode}', '${visit.visitPassword}', '${visit.assignmentId}')" class="btn-success">شروع/تکمیل بازدید</button>
                `;
                assignedVisitsListDiv.appendChild(visitItem);
            });
        }
    } else {
        const errorMsg = (result.data && result.data.message) || result.message || 'خطای نامشخص';
        assignedVisitsListDiv.innerHTML = `<p class="no-data-message" style="color:red;">خطا در بارگذاری لیست بازدیدها: ${errorMsg}</p>`;
        // Display error in a more prominent place if needed, e.g., loginMessageArea if dashboard fails to load
        if(loginMessageArea && loginSection.classList.contains('hidden')) displayMessage(loginMessageArea, `خطا در بارگذاری بازدیدها: ${errorMsg}`, false);
    }
}

async function startAssignedVisit(classCode, visitPassword, assignmentId) {
    const userId = sessionStorage.getItem('loggedInUser');
    if (!userId) {
        alert('خطا: اطلاعات کاربر یافت نشد. لطفاً مجدداً وارد شوید.');
        logout();
        return;
    }
    // Use a general message area, or loginMessageArea if no dedicated dashboard message area
    const generalMessageArea = document.getElementById('registeredUserDashboardMessageArea') || loginMessageArea; 
    if(generalMessageArea) displayMessage(generalMessageArea, 'در حال اعتبارسنجی و بارگذاری فرم بازدید...', true);

    const result = await callAppsScriptUser('validateAssignedVisitAccess', { userId, classCode, visitPassword });
    console.log("Validate Assigned Visit Access:", result);

    if (result.status === 'success' && result.data && result.data.isValid === true) {
        sessionStorage.setItem('currentClassCode', result.data.classCode);
        sessionStorage.setItem('currentVisitPassword', String(result.data.visitPassword));
        sessionStorage.setItem('currentClassName', result.data.className);
        sessionStorage.setItem('currentAssignmentId', result.data.assignmentId || assignmentId || ''); // Ensure assignmentId is stored

        if(userWelcomeMessageEl) userWelcomeMessageEl.innerHTML = `فرم بازدید برای کلاس: <strong>${result.data.className}</strong> (کد: ${result.data.classCode}، شماره بازدید: ${result.data.visitPassword})`;
        showUserSection('userFormSection');
        loadUserFormStructure(formSectionsContainerEl, false, 'formNavigation');
        if(downloadUserFormPdfButton) downloadUserFormPdfButton.style.display = 'inline-block';

        if(generalMessageArea) { generalMessageArea.classList.add('hidden'); generalMessageArea.innerHTML = '';}
    } else if (result.status === 'success' && result.data && result.data.isValid === false) {
        if(generalMessageArea) displayMessage(generalMessageArea, result.data.message, false);
    } else {
        if(generalMessageArea) displayMessage(generalMessageArea, (result.data && result.data.message) || result.message || 'خطا در دسترسی به فرم بازدید.', false);
    }
}

function showChangePasswordForm() {
    if (registeredUserMainContent) registeredUserMainContent.classList.add('hidden');
    if (changePasswordOwnFormContainer) changePasswordOwnFormContainer.classList.remove('hidden');
    if (changePasswordOwnMessageArea) changePasswordOwnMessageArea.classList.add('hidden');
    const currentPassEl = document.getElementById('currentPassword');
    const newPassEl = document.getElementById('newPasswordOwn');
    const confirmPassEl = document.getElementById('confirmNewPasswordOwn');
    if (currentPassEl) currentPassEl.value = '';
    if (newPassEl) newPassEl.value = '';
    if (confirmPassEl) confirmPassEl.value = '';
}

function hideChangePasswordForm() {
    if (changePasswordOwnFormContainer) changePasswordOwnFormContainer.classList.add('hidden');
    if (registeredUserMainContent) registeredUserMainContent.classList.remove('hidden');
    if (changePasswordOwnMessageArea) changePasswordOwnMessageArea.classList.add('hidden');
}

async function handleChangeOwnPassword() {
    const currentPasswordInput = document.getElementById('currentPassword');
    const newPasswordInput = document.getElementById('newPasswordOwn');
    const confirmNewPasswordInput = document.getElementById('confirmNewPasswordOwn');
    if(!currentPasswordInput || !newPasswordInput || !confirmNewPasswordInput || !changePasswordOwnMessageArea) return;

    const currentPassword = currentPasswordInput.value;
    const newPassword = newPasswordInput.value;
    const confirmNewPassword = confirmNewPasswordInput.value;

    if (!currentPassword || !newPassword || !confirmNewPassword) {
        displayMessage(changePasswordOwnMessageArea, 'تمام فیلدها برای تغییر رمز عبور ضروری هستند.', false);
        return;
    }
    if (newPassword.length < 4) {
        displayMessage(changePasswordOwnMessageArea, 'رمز عبور جدید باید حداقل ۴ کاراکتر باشد.', false);
        return;
    }
    if (newPassword !== confirmNewPassword) {
        displayMessage(changePasswordOwnMessageArea, 'رمز عبور جدید و تکرار آن یکسان نیستند.', false);
        return;
    }
    if (currentPassword === newPassword) {
        displayMessage(changePasswordOwnMessageArea, 'رمز عبور جدید نمی‌تواند با رمز عبور فعلی یکسان باشد.', false);
        return;
    }

    const loggedInUserId = sessionStorage.getItem('loggedInUser');
    if (!loggedInUserId) {
        displayMessage(changePasswordOwnMessageArea, 'خطا: اطلاعات کاربر برای تغییر رمز یافت نشد. لطفاً مجدداً وارد شوید.', false);
        logout();
        return;
    }

    displayMessage(changePasswordOwnMessageArea, 'در حال تغییر رمز عبور...', true);
    const result = await callAppsScriptUser('changeOwnPassword', {
        userId: loggedInUserId,
        oldPassword: currentPassword,
        newPassword: newPassword
    });
    console.log("Change Own Password Result:", result);

    if (result.status === 'success' && result.data && result.data.success === true) {
        displayMessage(changePasswordOwnMessageArea, result.data.message, true);
        currentPasswordInput.value = '';
        newPasswordInput.value = '';
        confirmNewPasswordInput.value = '';
         setTimeout(hideChangePasswordForm, 2000); // Hide form after successful change
    } else if (result.status === 'success' && result.data && result.data.success === false) {
        displayMessage(changePasswordOwnMessageArea, result.data.message, false);
    } else { // Includes result.status === 'error'
        displayMessage(changePasswordOwnMessageArea, (result.data && result.data.message) || result.message || 'خطا در هنگام تغییر رمز عبور.', false);
    }
}


// --- User Form Handling & Submission ---
function loadUserFormStructure(containerToPopulate, forAdminView = false, navContainerId = 'formNavigation') { 
    // This function is used by user form and admin's view form.
    // forAdminView will be false when called from user.js
    const navContainer = document.getElementById(navContainerId); 
    const sectionsContainer = containerToPopulate; 
    if (!sectionsContainer) { console.error("Form sections container not found."); return; }
    if (navContainer) navContainer.innerHTML = ''; 
    sectionsContainer.innerHTML = ''; 

    formStructure.forEach((section, index) => { 
        if (navContainer) { // This will be true for user form
            const navButton = document.createElement('button'); 
            navButton.textContent = section.name; 
            navButton.type = "button"; 
            navButton.onclick = () => showFormSection(section.id, navContainerId, sectionsContainer.id); 
            if (index === 0) navButton.classList.add('active'); 
            navContainer.appendChild(navButton); 
        } 
        const sectionDiv = document.createElement('div'); 
        sectionDiv.id = `section-${section.id}`; // User form sections don't need "review-" prefix
        sectionDiv.className = 'form-section'; 
        if (index !== 0) sectionDiv.classList.add('hidden'); 
        else sectionDiv.classList.remove('hidden'); 
        sectionDiv.innerHTML = `<h4>${section.name}</h4>`; 
        let currentSubSectionTitle = null; 
        if (!section.fields || section.fields.length === 0) { 
            sectionDiv.innerHTML += '<p><em>هنوز سوالی برای این بخش تعریف نشده است.</em></p>'; 
        } else { 
            section.fields.forEach(field => { 
                if (field.subSectionTitle && field.subSectionTitle !== currentSubSectionTitle) { 
                    currentSubSectionTitle = field.subSectionTitle; 
                    const subTitleEl = document.createElement('h5'); 
                    subTitleEl.className = 'sub-section-title'; 
                    subTitleEl.textContent = currentSubSectionTitle; 
                    sectionDiv.appendChild(subTitleEl); 
                } 
                const fieldGroup = document.createElement('div'); 
                fieldGroup.className = 'form-field-group'; 
                const label = document.createElement('label'); 
                label.htmlFor = field.id; 
                label.textContent = field.label + (field.required ? ' (ضروری)*' : ''); 
                fieldGroup.appendChild(label); 
                if (field.type === 'textarea') { 
                    const el = document.createElement('textarea'); 
                    el.id = field.id; el.name = field.id; 
                    if(field.placeholder) el.placeholder = field.placeholder; 
                    if(field.required) el.required = true; 
                    fieldGroup.appendChild(el); 
                } else if (field.type === 'select' || field.type === 'select_score') { 
                    const el = document.createElement('select'); 
                    el.id = field.id; el.name = field.id; 
                    if(field.required) el.required = true; 
                    (field.options || []).forEach((optText,optIdx) => { 
                        const option = document.createElement('option'); 
                        option.value = (field.type === 'select_score' && optText.includes('(')) ? optText.match(/\d+/)[0] : ( (optText === '--- انتخاب کنید ---' || optText === '') ? '' : optText ); 
                        option.textContent = optText || '--- انتخاب کنید ---'; 
                        if(optText === '' && optIdx === 0) {option.disabled = true; option.selected = true;} 
                        el.appendChild(option); 
                    }); 
                    fieldGroup.appendChild(el);  
                } else if (field.type === 'radio' || field.type === 'checkbox') { 
                    const inputGroupDiv = document.createElement('div'); 
                    inputGroupDiv.className = 'form-check-group'; 
                    (field.options || []).forEach(optValue => { 
                        const wrapperDiv = document.createElement('div'); 
                        const input = document.createElement('input'); input.type = field.type; 
                        input.id = `${field.id}_${optValue.replace(/\s+/g, '').replace(/[()]/g, '')}`; input.name = field.id; input.value = optValue; 
                        if(field.required && field.type==='radio') input.required = true; 
                        input.className = 'form-check-input';  
                        const optLabel = document.createElement('label'); optLabel.htmlFor = input.id; optLabel.textContent = optValue; optLabel.className = 'form-check-label'; 
                        wrapperDiv.appendChild(input); wrapperDiv.appendChild(optLabel); inputGroupDiv.appendChild(wrapperDiv); 
                    }); 
                    fieldGroup.appendChild(inputGroupDiv); 
                } else if (field.type === 'percentage_rows') { 
                    field.rows.forEach(row => { 
                        const rowDiv = document.createElement('div'); rowDiv.style.display = 'flex'; rowDiv.style.alignItems = 'center'; rowDiv.style.marginBottom = '0.5rem'; 
                        const rowLabel = document.createElement('label'); rowLabel.htmlFor = row.id; rowLabel.textContent = row.label + ": "; rowLabel.style.width = '150px'; rowLabel.style.marginRight = '10px'; rowLabel.style.flexShrink = '0'; 
                        rowDiv.appendChild(rowLabel); 
                        const selectEl = document.createElement('select'); selectEl.id = row.id; selectEl.name = row.id; 
                        if (field.required) selectEl.required = true; 
                        (field.options || []).forEach(opt => { const optionEl = document.createElement('option'); optionEl.value = opt; optionEl.textContent = opt + '%'; selectEl.appendChild(optionEl); }); 
                        selectEl.style.flexGrow = '1'; rowDiv.appendChild(selectEl); fieldGroup.appendChild(rowDiv); 
                    }); 
                } else if (field.type === 'checkbox_with_other') { 
                    const inputGroupDiv = document.createElement('div'); inputGroupDiv.className = 'form-check-group'; 
                    (field.options || []).forEach(optValue => { 
                        const wrapperDiv = document.createElement('div'); const input = document.createElement('input'); input.type = 'checkbox'; 
                        input.id = `${field.id}_${optValue.replace(/\s+/g, '').replace(/[()]/g, '')}`; input.name = field.id; input.value = optValue; input.className = 'form-check-input'; 
                        const optLabel = document.createElement('label'); optLabel.htmlFor = input.id; optLabel.textContent = optValue; optLabel.className = 'form-check-label'; 
                        wrapperDiv.appendChild(input); wrapperDiv.appendChild(optLabel); inputGroupDiv.appendChild(wrapperDiv); 
                    }); 
                    const otherWrapperDiv = document.createElement('div'); const otherInput = document.createElement('input'); otherInput.type = 'checkbox'; 
                    otherInput.id = `${field.id}_other_checkbox`; otherInput.name = field.id; otherInput.value = field.otherOptionLabel || 'سایر'; otherInput.className = 'form-check-input';  
                    const otherLabel = document.createElement('label'); otherLabel.htmlFor = otherInput.id; otherLabel.textContent = field.otherOptionLabel || 'سایر'; otherLabel.className = 'form-check-label'; 
                    otherWrapperDiv.appendChild(otherInput); otherWrapperDiv.appendChild(otherLabel); 
                    const otherTextInput = document.createElement('input'); otherTextInput.type = 'text'; otherTextInput.id = `${field.id}_other_text`; otherTextInput.name = `${field.id}_other_text`; 
                    otherTextInput.placeholder = 'لطفاً توضیح دهید'; otherTextInput.classList.add('sub-input-text', 'hidden'); 
                    otherWrapperDiv.appendChild(otherTextInput); inputGroupDiv.appendChild(otherWrapperDiv); fieldGroup.appendChild(inputGroupDiv); 
                    otherInput.addEventListener('change', (e) => { 
                        if (e.target.checked) { 
                            otherTextInput.classList.remove('hidden'); 
                            if (field.required && !Array.from(document.querySelectorAll(`input[name="${field.id}"][type="checkbox"]:checked:not(#${otherInput.id})`)).some(cb => cb.checked) ) { // Required only if no other checkbox is selected
                                otherTextInput.required = true; 
                            }
                        } else { 
                            otherTextInput.classList.add('hidden'); 
                            otherTextInput.value = ''; 
                            otherTextInput.required = false;
                        } 
                    });
                } else if (field.type === 'number' || field.type === 'date' || field.type === 'time' || field.type === 'text'){ 
                    const el = document.createElement('input'); el.type = field.type; el.id = field.id; el.name = field.id; 
                    if(field.placeholder) el.placeholder = field.placeholder; 
                    if(field.required) el.required = true; 
                    if (field.type === 'number') { el.min = "0"; } fieldGroup.appendChild(el); 
                } 
                
                // Handle dependsOn logic
                if (field.dependsOn) {
                    fieldGroup.classList.add('hidden'); // Hide by default if dependent
                    const sourceElements = document.getElementsByName(field.dependsOn);
                    
                    const checkDependency = () => {
                        let showField = false;
                        if (sourceElements.length > 0 && sourceElements[0].type === 'checkbox') {
                            // For checkbox, show if any of the checked values match dependsValue (if dependsValue is an array or single string)
                            // or if the specific checkbox with value dependsValue is checked.
                            const checkedSource = Array.from(sourceElements).filter(cb => cb.checked && cb.value === field.dependsValue);
                            if (checkedSource.length > 0) {
                                showField = true;
                            }
                        } else if (sourceElements.length > 0 && sourceElements[0].type === 'radio') {
                            const checkedRadio = document.querySelector(`input[name="${field.dependsOn}"]:checked`);
                            if (checkedRadio && checkedRadio.value === field.dependsValue) {
                                showField = true;
                            }
                        } else { // For select or other input types
                            const sourceElement = document.getElementById(field.dependsOn);
                            if (sourceElement && sourceElement.value === field.dependsValue) {
                                showField = true;
                            }
                        }

                        if (showField) {
                            fieldGroup.classList.remove('hidden');
                            // Make child fields required if parent is shown and they were originally required
                            const childInput = fieldGroup.querySelector('input, select, textarea');
                            if (childInput && field.required) childInput.required = true;
                        } else {
                            fieldGroup.classList.add('hidden');
                            // Make child fields not required if parent is hidden
                            const childInput = fieldGroup.querySelector('input, select, textarea');
                            if (childInput && field.required) {
                                childInput.required = false;
                                // Optionally clear value: childInput.value = '';
                            }
                        }
                    };

                    sourceElements.forEach(el => el.addEventListener('change', checkDependency));
                    checkDependency(); // Initial check
                }
                sectionDiv.appendChild(fieldGroup); 
            }); 
        } 
        sectionsContainer.appendChild(sectionDiv); 
    }); 
    if (formStructure.length > 0 && navContainer) { 
        showFormSection(formStructure[0].id, navContainerId, sectionsContainer.id); 
    } 
}

function showFormSection(sectionIdToShow, navId = 'formNavigation', containerId = 'formSectionsContainer') { 
    // This function is used by user form for its own navigation
    const activeNavContainer = document.getElementById(navId); 
    const activeSectionsContainer = document.getElementById(containerId); 
    if (!activeSectionsContainer) { console.error("Form sections container not found for showFormSection."); return; }
    if(activeNavContainer) activeNavContainer.querySelectorAll('button').forEach(btn => btn.classList.remove('active')); 
    activeSectionsContainer.querySelectorAll('.form-section').forEach(sec => sec.classList.add('hidden')); 
    const targetSectionInfo = formStructure.find(s => s.id === sectionIdToShow); 
    if (targetSectionInfo) { 
        if(activeNavContainer){ 
            const activeButton = Array.from(activeNavContainer.querySelectorAll('button')).find(btn => btn.textContent === targetSectionInfo.name); 
            if (activeButton) activeButton.classList.add('active'); 
        } 
        const sectionToShowEl = document.getElementById(`section-${sectionIdToShow}`); // User form sections don't have "review-" prefix
        if (sectionToShowEl && activeSectionsContainer && activeSectionsContainer.contains(sectionToShowEl)) { 
            sectionToShowEl.classList.remove('hidden'); 
            if (activeNavContainer && activeNavContainer.offsetParent !== null) { 
                const navBarHeight = activeNavContainer.offsetHeight; 
                const targetScrollY = sectionToShowEl.getBoundingClientRect().top + window.pageYOffset - navBarHeight - 20; // Extra 20px padding
                window.scrollTo({ top: targetScrollY, behavior: 'smooth' }); 
            } 
        } 
    } 
}

async function submitUserForm() { 
    if (!userFormMessageArea) return;
    userFormMessageArea.classList.add('hidden'); 
    userFormMessageArea.innerHTML = '';
    const formData = {}; 
    let firstEmptyField = null; 
    let firstEmptyFieldSectionId = null; 
    let firstRadioCheckboxGroupField = null; 

    for (const section of formStructure) {
        if (section.fields && section.fields.length > 0) {
            for (const field of section.fields) {
                const fieldGroupElement = document.getElementById(field.id)?.closest('.form-field-group') || 
                                      document.querySelector(`input[name='${field.id}']`)?.closest('.form-field-group') ||
                                      (field.type === 'percentage_rows' && field.rows.length > 0 ? document.getElementById(field.rows[0].id)?.closest('.form-field-group') : null);

                // Skip validation for hidden dependent fields
                if (fieldGroupElement && fieldGroupElement.classList.contains('hidden') && field.dependsOn) {
                    formData[field.id] = null; 
                    if (field.type === 'checkbox_with_other') formData[`${field.id}_other_text`] = null; 
                    if (field.type === 'percentage_rows') field.rows.forEach(row => formData[row.id] = null); 
                    continue; 
                }

                if (field.type === 'radio') { 
                    const checkedRadio = document.querySelector(`input[name="${field.id}"]:checked`); 
                    formData[field.id] = checkedRadio ? checkedRadio.value : ''; 
                    if (field.required && !formData[field.id] && !firstEmptyField) { 
                        firstEmptyField = document.querySelector(`input[name="${field.id}"]`); 
                        firstEmptyFieldSectionId = section.id; 
                        firstRadioCheckboxGroupField = field;
                    } 
                } else if (field.type === 'checkbox' || field.type === 'checkbox_with_other') { 
                    const checkedBoxes = Array.from(document.querySelectorAll(`input[name="${field.id}"][type="checkbox"]:checked`)); 
                    let finalValues = []; 
                    let isOtherSelectedAndFilled = false; 
                    let otherCheckboxSelectedButTextEmpty = false;

                    if (field.type === 'checkbox_with_other') { 
                        const otherCheckbox = document.getElementById(`${field.id}_other_checkbox`); 
                        const otherTextInput = document.getElementById(`${field.id}_other_text`); 
                        if (otherCheckbox && otherCheckbox.checked && otherTextInput) { 
                            const otherTextValue = otherTextInput.value.trim(); 
                            formData[`${field.id}_other_text`] = otherTextValue; 
                            if (otherTextValue) { 
                                isOtherSelectedAndFilled = true; 
                                finalValues.push(otherTextValue); // Add the text itself
                            } else if (field.required) {
                                otherCheckboxSelectedButTextEmpty = true; // Mark that "other" was checked but text is empty
                            }
                        } else { formData[`${field.id}_other_text`] = ''; } 
                    } 
                    
                    checkedBoxes.forEach(cb => { 
                        if (cb.id !== `${field.id}_other_checkbox`){ // Don't add the generic "other" value if specific text is captured
                           finalValues.push(cb.value); 
                        }
                    }); 
                    formData[field.id] = finalValues.join(', '); 

                    if (field.required && finalValues.length === 0 && !firstEmptyField ) {
                         // If "other" was checked but text empty, this is the error
                        if (otherCheckboxSelectedButTextEmpty) {
                            firstEmptyField = document.getElementById(`${field.id}_other_text`);
                        } else {
                            firstEmptyField = document.querySelector(`input[name="${field.id}"]`); 
                        }
                        firstEmptyFieldSectionId = section.id; 
                        firstRadioCheckboxGroupField = field;
                    } 
                } else if (field.type === 'percentage_rows') { 
                    let allRowsFilledForPercentage = true; 
                    let totalPercentage = 0;
                    field.rows.forEach(row => { 
                        const selectEl = document.getElementById(row.id); 
                        const value = selectEl ? selectEl.value : ''; 
                        formData[row.id] = value; 
                        if (value) totalPercentage += parseInt(value);
                        if (field.required && !value && allRowsFilledForPercentage) { 
                            allRowsFilledForPercentage = false; 
                            if(!firstEmptyField) { 
                                firstEmptyField = selectEl; 
                                firstEmptyFieldSectionId = section.id; 
                                firstRadioCheckboxGroupField = field; 
                            } 
                        } 
                    });
                    if (field.required && totalPercentage !== 100 && allRowsFilledForPercentage && !firstEmptyField) {
                        firstEmptyField = document.getElementById(field.rows[0].id); // Point to first select in group
                        firstEmptyFieldSectionId = section.id;
                        firstRadioCheckboxGroupField = field; // Mark the whole group
                        displayMessage(userFormMessageArea, `مجموع درصدها برای سوال "${field.label.replace(' (ضروری)*', '')}" باید ۱۰۰٪ باشد.`, false);
                        showFormSection(firstEmptyFieldSectionId, 'formNavigation', 'formSectionsContainer');
                        if (firstEmptyField.focus && firstEmptyField.type !== 'hidden') firstEmptyField.focus();
                        return; // Stop submission
                    }
                } else { 
                    const fieldElement = document.getElementById(field.id); 
                    if (fieldElement) { 
                        formData[field.id] = fieldElement.value.trim(); 
                        if (field.required && !formData[field.id] && !firstEmptyField) { 
                            firstEmptyField = fieldElement; 
                            firstEmptyFieldSectionId = section.id;
                        }
                    }
                } 
            }
        }
        if (firstEmptyField) break; // Stop iterating sections if an empty field is found
    }

    if (firstEmptyField) { 
        const fieldWithError = firstRadioCheckboxGroupField || formStructure.flatMap(s => s.fields).find(f => f.id === firstEmptyField.id || f.id === firstEmptyField.name || (f.rows && f.rows.find(r=>r.id === firstEmptyField.id))); 
        let fieldLabelText = fieldWithError ? (fieldWithError.label) : (firstEmptyField.labels && firstEmptyField.labels.length > 0 ? firstEmptyField.labels[0].textContent : 'فیلد نامشخص'); 
        fieldLabelText = fieldLabelText.replace(' (ضروری)*', '');

        if (fieldWithError && fieldWithError.type === 'percentage_rows' && firstEmptyField.id !== fieldWithError.id && fieldWithError.rows) { 
            const subRow = fieldWithError.rows.find(r => r.id === firstEmptyField.id); 
            if (subRow) fieldLabelText = `${fieldWithError.label.replace(' (ضروری)*','')} - ${subRow.label}`; 
        } else if (fieldWithError && fieldWithError.type === 'checkbox_with_other' && firstEmptyField.id === `${fieldWithError.id}_other_text`){ 
            fieldLabelText = fieldWithError.label + " - بخش توضیحات گزینه '" + (fieldWithError.otherOptionLabel || "سایر") + "'"; 
        } 
        const sectionName = formStructure.find(s => s.id === firstEmptyFieldSectionId)?.name || 'بخش نامشخص'; 
        displayMessage(userFormMessageArea, `لطفاً فیلد "${fieldLabelText}" در بخش "${sectionName}" را پر کنید.`, false); 
        showFormSection(firstEmptyFieldSectionId, 'formNavigation', 'formSectionsContainer'); 
        if (firstEmptyField.focus && firstEmptyField.type !== 'hidden') firstEmptyField.focus(); 
        else if (firstEmptyField.scrollIntoView) firstEmptyField.scrollIntoView({behavior: "smooth", block: "center"});
        return; 
    } 
    
    displayMessage(userFormMessageArea, 'در حال ارسال اطلاعات فرم...', true); 
    const classCode = sessionStorage.getItem('currentClassCode'); 
    const visitPassword = sessionStorage.getItem('currentVisitPassword'); 
    const classNameSubmittedFor = sessionStorage.getItem('currentClassName'); 
    const submittedByUserID = sessionStorage.getItem('currentUserType') === 'user' ? sessionStorage.getItem('loggedInUser') : null;

    if (!classCode || !visitPassword) {
        const currentUserType = sessionStorage.getItem('currentUserType');
        if (currentUserType === 'user') {
             displayMessage(userFormMessageArea, 'خطا: اطلاعات کلاس و بازدید برای ارسال فرم مشخص نشده است. لطفاً از داشبورد خود یک بازدید را انتخاب کنید.', false);
        } else { 
             displayMessage(userFormMessageArea, 'خطا: اطلاعات کلاس و بازدید برای ارسال فرم یافت نشد. لطفاً مجدداً وارد شوید.', false);
        }
        return;
    }

    const payload = { classCode, visitPassword, classNameSubmittedFor, formData: JSON.stringify(formData), submittedByUserID }; 
    console.log("Submitting user form with payload:", payload);
    callAppsScriptUser('submitUserForm', payload) 
    .then(result => { 
        console.log("Submit User Form Result:", result);
        if (result.status === 'success' && result.data && result.data.success === true) { 
            displayMessage(userFormMessageArea, result.data.message, true); 
            if(downloadUserFormPdfButton) downloadUserFormPdfButton.style.display = 'none'; // Hide after successful submission
            setTimeout(() => { logout(); }, 3000); 
        } else if (result.status === 'success' && result.data && result.data.alreadySubmitted === true) {
            displayMessage(userFormMessageArea, result.data.message, false);
            if(downloadUserFormPdfButton) downloadUserFormPdfButton.style.display = 'none';
            setTimeout(() => { logout(); }, 3000); 
        }
        else { 
            displayMessage(userFormMessageArea, (result.data && result.data.message) || result.message || 'خطا در ارسال فرم.', false); 
        } 
    }) 
    .catch(error => { 
        displayMessage(userFormMessageArea, 'خطای اساسی در هنگام ارسال: ' + error.message, false); 
        console.error("submitUserForm CATCH:", error);
    }); 
}

// --- PDF Generation for User Form (Preview/Draft) ---
async function downloadUserFormAsPDF() {
    const formContent = document.getElementById('formSectionsContainer');
    const welcomeMessage = document.getElementById('userWelcomeMessage');
    const pdfButton = document.getElementById('downloadUserFormPdfButton');

    if (!formContent || !welcomeMessage || !pdfButton) {
        alert('خطا: المان‌های لازم برای تولید PDF یافت نشدند.');
        return;
    }
    const { jsPDF } = window.jspdf;
    if (!jsPDF || typeof html2canvas === 'undefined') {
        alert("کتابخانه‌های jsPDF یا html2canvas بارگذاری نشده‌اند.");
        return;
    }

    pdfButton.textContent = "در حال آماده‌سازی PDF...";
    pdfButton.disabled = true;

    // Temporarily show all form sections for PDF generation
    const hiddenSections = [];
    formContent.querySelectorAll('.form-section.hidden').forEach(sec => {
        sec.classList.remove('hidden');
        hiddenSections.push(sec);
    });
    // Hide navigation within PDF
    const navElement = document.getElementById('formNavigation'); // ID of the user's form navigation
    const originalNavDisplay = navElement ? navElement.style.display : "";
    if (navElement) navElement.style.display = 'none';

    try {
        const canvas = await html2canvas(formContent, {
            scale: 1.5,
            useCORS: true,
            logging: false,
            windowWidth: formContent.scrollWidth,
            windowHeight: formContent.scrollHeight,
             onclone: (doc) => {
                 const navInClone = doc.getElementById('formNavigation');
                 if (navInClone) navInClone.style.display = 'none';
                 doc.querySelectorAll('.form-section.hidden').forEach(s => s.classList.remove('hidden'));
            }
        });

        const imgData = canvas.toDataURL('image/jpeg', 0.85);
        const pdf = new jsPDF({ orientation: 'p', unit: 'pt', format: 'a4' });

        // Assuming vazirmatnFont is available globally (or pass it if needed)
        // This requires the same vazirmatnFont variable defined in admin.js to be defined here,
        // OR you can include the font definition directly in this file too.
        // For now, we'll assume it's defined elsewhere or rely on standard fonts if not.
        try {
            if (typeof vazirmatnFont !== 'undefined' && vazirmatnFont && vazirmatnFont !== 'PLACEHOLDER_FOR_VAZIRMATN_FONT_BASE64_STRING' && !vazirmatnFont.startsWith('AAEAAAASAQAABAAgR0RFRgAAAAgAAAHgR0RFRgAbOBMAAAK0T1MvMgAAAABAAAAAYGNtYXAAAA')) {
                pdf.addFileToVFS("Vazirmatn-Regular.ttf", vazirmatnFont);
                pdf.addFont("Vazirmatn-Regular.ttf", "Vazirmatn", "normal");
                pdf.setFont("Vazirmatn");
            } else {
                 console.warn("Vazirmatn font for user PDF not available, using default.");
                 pdf.setFont("helvetica", "normal");
            }
        } catch(e){
             console.warn("Error loading Vazirmatn font for user PDF, using default.", e);
             pdf.setFont("helvetica", "normal");
        }
        pdf.setR2L(true);

        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 40;
        const contentWidth = pdfWidth - (2 * margin);
        const imgTargetWidth = contentWidth;
        const imgTargetHeight = (imgProps.height * imgTargetWidth) / imgProps.width;
        let currentY = margin;

        pdf.setFontSize(14);
        const reportTitleText = welcomeMessage.textContent || "پیش‌نویس فرم بازدید";
        const titleLines = pdf.splitTextToSize(reportTitleText, contentWidth - 20);
        pdf.text(titleLines, pdfWidth / 2, currentY, { align: 'center' });
        currentY += (titleLines.length * 14) + 20;

        let remainingImgHeight = imgTargetHeight;
        let imgCurrentSourceY = 0;

        while (remainingImgHeight > 0) {
            if (currentY > margin && (pageHeight - currentY - margin < 20)) {
                pdf.addPage();
                currentY = margin;
            }
            let pageSpaceForImage = pageHeight - currentY - margin;
            if (pageSpaceForImage <= 0 && remainingImgHeight > 0) {
                 pdf.addPage();
                 currentY = margin;
                 pageSpaceForImage = pageHeight - (2 * margin);
            }

            const heightToDrawOnPdf = Math.min(remainingImgHeight, pageSpaceForImage);
            const sourceImageHeightSlice = (heightToDrawOnPdf / imgTargetHeight) * canvas.height;

            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = sourceImageHeightSlice;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(canvas, 0, imgCurrentSourceY, canvas.width, sourceImageHeightSlice, 0, 0, tempCanvas.width, tempCanvas.height);
            const pageImgData = tempCanvas.toDataURL('image/jpeg', 0.85);

            if (heightToDrawOnPdf > 0) {
               pdf.addImage(pageImgData, 'JPEG', margin, currentY, imgTargetWidth, heightToDrawOnPdf);
            }
            
            remainingImgHeight -= heightToDrawOnPdf;
            imgCurrentSourceY += sourceImageHeightSlice;
            currentY += heightToDrawOnPdf + 10;
        }
        
        const classCode = sessionStorage.getItem('currentClassCode') || "class";
        const visitPassword = sessionStorage.getItem('currentVisitPassword') || "visit";
        pdf.save(`پیش‌نویس_فرم_${classCode}_${visitPassword}.pdf`);

    } catch (error) {
        console.error("Error generating user form PDF:", error);
        alert("خطا در تولید PDF: " + error.message);
    } finally {
        pdfButton.textContent = "دانلود پیش‌نویس PDF";
        pdfButton.disabled = false;
        // Restore visibility of sections and nav
        hiddenSections.forEach(sec => sec.classList.add('hidden'));
        if (navElement) navElement.style.display = originalNavDisplay || 'grid';
        
        const activeNavButton = navElement ? navElement.querySelector('button.active') : null;
        if (activeNavButton) {
            const onclickAttr = activeNavButton.getAttribute('onclick');
            if(onclickAttr) {
                const activeSectionIdMatch = onclickAttr.match(/'([^']+)'/);
                if (activeSectionIdMatch && activeSectionIdMatch[1]) {
                     showFormSection(activeSectionIdMatch[1], 'formNavigation', 'formSectionsContainer');
                }
            }
        } else if (formStructure.length > 0 && navElement){ 
            showFormSection(formStructure[0].id, 'formNavigation', 'formSectionsContainer');
        }
    }
}


// --- Initial Load for User Page ---
document.addEventListener('DOMContentLoaded', () => {
    // Check if crypto.subtle is available (for potential future use, not currently used for user passwords)
    if (window.isSecureContext === false && location.hostname !== "localhost" && location.hostname !== "127.0.0.1") {
        console.warn("User Page: crypto.subtle is not available. Ensure HTTPS if sensitive operations are added.");
    }
    // Check if user is already logged in (e.g. from previous session)
    const currentUserType = sessionStorage.getItem('currentUserType');
    const loggedInUser = sessionStorage.getItem('loggedInUser');
    const displayName = sessionStorage.getItem('loggedInUserDisplayName');

    if (currentUserType === 'user' && loggedInUser) {
        if (registeredUserWelcomeMessageEl) registeredUserWelcomeMessageEl.textContent = `خوش آمدید، ${displayName || loggedInUser}!`;
        showUserSection('registeredUserDashboardSection');
        loadAssignedVisits();
    } else {
        // If not logged in or session expired, show login section
        showUserSection('loginSection');
    }
});

// --- Font for PDF (Base64 encoded Vazirmatn-Regular.ttf) ---
// This should be the same long Base64 string as in admin.js for PDF generation consistency.
// If you only want PDF download for admin, you can remove jsPDF/html2canvas from user.html and this font.
const vazirmatnFont = `PLACEHOLDER_FOR_VAZIRMATN_FONT_BASE64_STRING`; // <<<< این بخش باید با محتوای کامل فونت جایگزین شود