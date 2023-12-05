<?php

// add custom fonts end library typing
function add_custom_fonts(){
    ?>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typed.js/2.0.11/typed.min.js" integrity="sha512-BdHyGtczsUoFcEma+MfXc71KJLv/cd+sUsUaYYf2mXpfG/PtBjNXsPo78+rxWjscxUYN2Qr2+DbeGGiJx81ifg==" crossorigin="anonymous"></script>
    <?php
}
add_action('wp_head', 'add_custom_fonts');
// end add custom fonts end library typing

function thim_child_enqueue_styles() {
	wp_enqueue_style( 'thim-parent-style', get_template_directory_uri() . '/style.css', array(), THIM_THEME_VERSION  );
}

add_action( 'wp_enqueue_scripts', 'thim_child_enqueue_styles', 1000 );
add_filter( 'thim-importer-demo-vc', '__return_true' );


add_action('wp_enqueue_scripts', 'enqueue_plugin_styles');

function register_course_post_type() {
    $labels = array(
        'name' => 'Courses',
        'singular_name' => 'Course',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'courses'),
    );

    register_post_type('courses', $args);
}
add_action('init', 'register_course_post_type');

// Đăng ký các trường dữ liệu meta cho 'courses'
function register_course_taxonomies() {
    $taxonomies = array(
        'course_level' => 'Trình Độ',
        'course_goal' => 'Mục Tiêu',
        'course_time' => 'Thời Gian',
    );

    foreach ($taxonomies as $slug => $label) {
        register_taxonomy($slug, 'courses', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => $label,
                'singular_name' => $label,
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $slug),
        ));
    }
}
add_action('init', 'register_course_taxonomies');


function register_course_custom_fields() {
    // Xóa các trường dữ liệu meta không cần thiết
    $fields = array('detail_level','course_month', 'detail_goal', 'course_summary', 'xemchitiet');
    foreach ($fields as $field) {
        unregister_post_meta('courses', $field);
    }
}
add_action('init', 'register_course_custom_fields');

function add_course_meta_boxes() {
    add_meta_box('course_summary', 'Course Summary', 'render_course_summary_meta_box', 'courses', 'normal', 'default');
}
add_action('add_meta_boxes', 'add_course_meta_boxes');

function render_course_summary_meta_box($post) {
    $level_terms = get_terms('course_level', array('hide_empty' => false));
    $goal_terms = get_terms('course_goal', array('hide_empty' => false));
    $time_terms = get_terms('course_time', array('hide_empty' => false));

    $selected_level = wp_get_post_terms($post->ID, 'course_level');
    $selected_goal = wp_get_post_terms($post->ID, 'course_goal');
    $selected_time = wp_get_post_terms($post->ID, 'course_time');

    $summary = get_post_meta($post->ID, 'course_summary', true);
    $detail_level = get_post_meta($post->ID, 'detail_level', true);
    $detail_goal = get_post_meta($post->ID, 'detail_goal', true);
    $detail_month = get_post_meta($post->ID, 'course_month', true);
    ?>

    <table id="newmeta">
        <thead>
        <tr>
            <th class="left"><label for="metakeyselect">Tên</label></th>
            <th><label for="metavalue">Giá trị</label></th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td id="newmetaleft" class="left"><label for="course_summary">Course Summary:</label></td>
            <td><textarea id="course_summary" name="course_summary" rows="2" cols="25"><?php echo esc_html($summary); ?></textarea></td>
        </tr>

        <tr>
            <td id="newmetaleft" class="left"><label for="detail_level">Mô tả trình độ:</label></td>
            <td><input type="text" id="detail_level" name="detail_level" value="<?php echo esc_attr($detail_level); ?>"></td>
        </tr>

        <tr>
            <td id="newmetaleft" class="left"><label for="detail_goal">Mô tả mục tiêu:</label></td>
            <td><input type="text" id="detail_goal" name="detail_goal" value="<?php echo esc_attr($detail_goal); ?>"></td>
        </tr>
        <tr>
            <td id="newmetaleft" class="left"><label for="detail_month">Tháng:</label></td>
            <td><input type="text" id="detail_month" name="detail_month" value="<?php echo esc_attr($detail_month); ?>"></td>
        </tr>

        </tbody>
    </table>
    <?php
}

function save_course_meta($post_id) {
    $fields = array('course_level', 'course_goal', 'course_time', 'course_month', 'detail_level', 'detail_goal', 'course_summary');

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            if ($field === 'course_summary') {
                update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
            } else {
                wp_set_post_terms($post_id, $_POST[$field], $field, false);
            }
        }
    }

    // Save detail_level and detail_goal
    $detail_level = isset($_POST['detail_level']) ? sanitize_text_field($_POST['detail_level']) : '';
    $detail_goal = isset($_POST['detail_goal']) ? sanitize_text_field($_POST['detail_goal']) : '';
    $detail_month = isset($_POST['detail_month']) ? sanitize_text_field($_POST['detail_month']) : '';
    update_post_meta($post_id, 'detail_level', $detail_level);
    update_post_meta($post_id, 'detail_goal', $detail_goal);
    update_post_meta($post_id, 'course_month', $detail_month);
}
add_action('save_post_courses', 'save_course_meta');

// Hàm lấy trình độ duy nhất của khóa học
function get_unique_course_levels() {
    $levels = get_terms('course_level', array('hide_empty' => false));
    $unique_levels = array(
        'levels' => array(),
        'combined' => array(),
    );

    foreach ($levels as $level) {
        $unique_levels['levels'][] = $level->name;
        $unique_levels['combined'][] = $level->name . ': ' . $level->description;
    }

    return $unique_levels;
}

// Hàm lấy mục tiêu duy nhất của khóa học
function get_unique_course_goals() {
    $goals = get_terms('course_goal', array('hide_empty' => false));
    $unique_goals = array(
        'goals' => array(),
        'combinedgoal' => array(),
    );

    foreach ($goals as $goal) {
        $unique_goals['goals'][] = $goal->name;
        $unique_goals['combinedgoal'][] = $goal->name . ': ' . $goal->description;
    }

    return $unique_goals;
}

// Hàm lấy thời gian duy nhất của khóa học
function get_unique_course_times() {
    $times = get_terms('course_time', array('hide_empty' => false));
    $unique_times = array(
        'times' => array(),
        'combinedtime' => array(),
    );

    foreach ($times as $time) {
        $unique_times['times'][] = $time->name;
        $unique_times['combinedtime'][] = $time->name . ': ' . $time->description;
    }

    return $unique_times;
}



// Shortcode to display the course filter form
function display_course_filter_form() {
    ob_start();
	$unique_levels = get_unique_course_levels();
    $levels = $unique_levels['levels'];
    $combined = $unique_levels['combined'];
	
	
	$unique_goals = get_unique_course_goals();
    $goals = $unique_goals['goals'];
    $combinedgoal = $unique_goals['combinedgoal'];
	
	
	$unique_times = get_unique_course_times();
    $times = $unique_times['times'];
    $combinedtime = $unique_times['combinedtime'];
    ?>
	<script>
jQuery(document).ready(function ($) {
     $(document).on('click', 'input[type="radio"]', function () {
        // Xóa class active từ tất cả các div form-flex--2
        $('.form-flex--2').removeClass('active');
        
        // Thêm class active vào div cha gần nhất của radio button được chọn
        $(this).closest('.form-flex--2').addClass('active');
    });
});

        jQuery(document).ready(function ($) {
            $('input[type="radio"]').on('click', function () {
                // Get the value of the selected radio button
                var selectedValue = $(this).val();

                // Find the corresponding span tag and update its content
                if ($(this).attr('name') === 'course-level') {
                    $('#trinh_do').text(selectedValue);
                    toggleActiveClass('id_1');
                } else if ($(this).attr('name') === 'course-goal') {
                    $('#muc_tieu').text(selectedValue);
                    toggleActiveClass('id_2');
                } else if ($(this).attr('name') === 'course-time') {
                    $('#thoi_gian').text(selectedValue);
                    toggleActiveClass('id_3');
                }
            });

            function toggleActiveClass(id) {
                // Remove "active" class from all elements with class "rm"
                $('.rm').removeClass('active');

                // Add "active" class to the element with the specified id
                $('#' + id).addClass('active');
            }
        });


$('#course-filter-form').on('submit', function (event) {
    event.preventDefault();
    $('#result-image').hide();
});

function resetPopup() {
    var overlay2 = document.querySelector('.overlay2');
    
    var popup = document.querySelector('.popupmb');
    
    overlay2.classList.remove('show-overlay2');

    popup.classList.remove('show-popup');
}


resetPopup();
	</script>

<script>
jQuery(document).ready(function ($) {
	

	
    $('.form-step').hide();
    $('.form-step[data-step="1"]').show();


var currentStepIndex = 0;

$(document).ready(function () {
    $('.form-step').eq(currentStepIndex).show();
    toggleActiveClass('id_' + (currentStepIndex + 1)); // Thêm lớp active cho form-step đầu tiên
});

$('.next-step').on('click', function (e) {
    e.preventDefault();

    var currentFormStep = $('.form-step').eq(currentStepIndex);
    currentFormStep.hide();
    currentStepIndex++;

    var nextFormStep = $('.form-step').eq(currentStepIndex);
    nextFormStep.show();
    
    toggleActiveClass('id_' + (currentStepIndex + 1));

});

$('.prev-step').on('click', function (e) {
    e.preventDefault();

    if (currentStepIndex > 0) {
        var currentFormStep = $('.form-step').eq(currentStepIndex);
        currentFormStep.hide();
        currentStepIndex--;

        var prevFormStep = $('.form-step').eq(currentStepIndex);
        prevFormStep.show();

        toggleActiveClass('id_' + (currentStepIndex + 1));
    }
});





function toggleActiveClass(id) {
    $('.rm').removeClass('active');
    $('#' + id).addClass('active');
}


    $('#id_1, #id_2, #id_3').on('click', function (e) {
        e.preventDefault();
        var stepNumber = $(this).data('step');
        $('.form-step').hide();
        $('.form-step[data-step="' + stepNumber + '"]').show();
    });
	$('#course-filter-form').on('submit', function (event) {
		event.preventDefault();

		let selectedLevels = $('input[name="course-level"]:checked').val();
		let selectedGoal = $('input[name="course-goal"]:checked').val();
		let selectedTime = $('input[name="course-time"]:checked').val();
		$.ajax({
			type: 'POST',
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			data: {
				action: 'filter_courses',
				levels: selectedLevels,
				goal: selectedGoal,
				time: selectedTime,
			},
			success: function (response) {
				$('#course-results').html(response);
			}
		});
	});

});

</script>
<script>
    function toggleActiveClass(element) {
        var rmElements = document.querySelectorAll('.rm');

        rmElements.forEach(function (el) {
            el.classList.remove('active');
        });

        element.classList.add('active');
    }
</script>

	<section id="course-filter-form2">
		<div class="stepp">
			<div class="roadmap">
				<div class="circle-large">
					<div class="circle-small">
						<span>1</span>
					</div>
				</div>

				<div class="rm" id="id_1" data-step="1" onclick="toggleActiveClass(this)">
					<div>
						<div class="h3 hnn--size--16">Trình độ hiện tại của bạn</div>
						<span id="trinh_do">Chọn đầu vào</span>
					</div>
					<i class="fa-solid fa-angle-right"></i>
				</div>
				<div class="rm"  id="id_2" data-step="2" onclick="toggleActiveClass(this)">
					<div>
						<div class="h3 hnn--size--16" >Mục tiêu</div>
						<span id="muc_tieu">Chọn mục tiêu</span>
					</div>
					<i class="fa-solid fa-angle-right"></i>
				</div>	
				<div class="rm"  id="id_3" data-step="3" onclick="toggleActiveClass(this)">
					<div>
						<div class="h3 hnn--size--16">Thời gian</div>
						<span id="thoi_gian" >Chọn thời gian</span>
					</div>
					<i class="fa-solid fa-angle-right"></i>
				</div>
			</div>
		</div>
		<div class="res2" id="ketqua">
			<div class="roadmap">
				<div class="circle-large">
					<div class="circle-small">
						<span>2</span>
					</div>
				</div>	
				<div style="font-size: 16px; font-weight: 700; margin-left: 20px">Lộ trình phù hợp cho bạn</div>	
			</div>		
		</div>		
		
	</section>	
<script>
document.querySelectorAll('input[name="course-level"]').forEach(function (radio) {
    radio.addEventListener('click', function () {
        var currentStep = parseInt(document.querySelector('.form-step.show').getAttribute('data-step'));
        document.querySelector('.form-step.show').classList.remove('show');
        document.querySelector('.form-step[data-step="' + (currentStep + 1) + '"]').classList.add('show');
        if (currentStep === 3) {
            submitForm();
        }
    });
});

</script>

	
    <form id="course-filter-form">
	<div class="stepp"><div class="overlay2"></div>
		<div class="popupmb">
		 <div class="close-popup">&#x2716;</div>
		 
<script>
document.getElementById('id_1').addEventListener('click', function() {
    var overlay2 = document.querySelector('.overlay2');
    var popup = document.querySelector('.popupmb');
    overlay2.classList.add('show-overlay2');
    popup.classList.add('show-popup');
});
document.querySelector('.close-popup').addEventListener('click', function() {
    var overlay2 = document.querySelector('.overlay2');
    var popup = document.querySelector('.popupmb');
    overlay2.classList.remove('show-overlay2');
    popup.classList.remove('show-popup');
});

document.querySelectorAll('label[for^="levels_"]').forEach(function (label) {
    label.addEventListener('click', function () {
        var currentStep = parseInt(document.querySelector('.form-step.show').getAttribute('data-step'));
        document.querySelector('.form-step.show').classList.remove('show');
        document.querySelector('.form-step[data-step="' + (currentStep + 1) + '"]').classList.add('show');
        if (currentStep === 3) {
            submitForm();
        }
    });
});

</script>

		<div class="form-step" data-step="1">	
			<div class="h3 hnn--size--16 text-mobile" >Trình độ hiện tại của bạn</div>
			<div class="form-flex--">
				<?php foreach ($levels as $index => $level) : ?>
				<label for="levels_<?php echo $level; ?>">
					<div class="form-flex--2">
						
						<div>
							<h3 style="font-size: 20px; font-weight: 700"><?php echo $level; ?></h3>
							<span style="line-height:21px;font-size: 14px; font-weight: 400"><?php echo $combined[$index]; ?></span>
						</div>
						
						
						<input type="radio" name="course-level" value="<?php echo $level; ?>" id="levels_<?php echo $level; ?>">
					</div>
					</label>
				<?php endforeach; ?>
			</div>
			
			<button class="next-step">Tiếp tục <i class="fa-solid fa-angle-right"></i></button>
				
		</div>
		
		<div class="form-step" data-step="2">	
			<div class="h3 hnn--size--16 text-mobile" >Mục tiêu</div>
			<div class="form-flex--">
			
				<?php foreach ($goals as $index => $goal) : ?>
				<label for="goal_<?php echo $goal; ?>">
				
					<div class="form-flex--2">
					
						<div>
							<h3 style="font-size: 20px; font-weight: 700"><?php echo $goal; ?></h3>
							<span style="line-height:21px;font-size: 14px; font-weight: 400"><?php echo $combinedgoal[$index]; ?></span>
						</div>
					
						<input type="radio" name="course-goal" value="<?php echo $goal; ?>" id="goal_<?php echo $goal; ?>">
					</div>
					</label>	
				<?php endforeach; ?>
			</div>
			
			<button class="prev-step"><i class="fa-solid fa-angle-left"></i>Quay lại</button>
			<button class="next-step">Tiếp tục <i class="fa-solid fa-angle-right"></i></button>

		</div>

		<div class="form-step" data-step="3">
		<div class="h3 hnn--size--16 text-mobile" >Thời gian</div>	
			<div class="form-flex--">
				<?php foreach ($times as $time) : ?>
				<label for="time_<?php  echo $time;
				?>">
				<div class="form-flex--2">
				
					<div>
						<h3 style="font-size: 20px; font-weight: 700"><?php
						$time1 = str_replace('nhỏ hơn', '<', $time);
						$time2 = str_replace('lớn hơn', '>', $time1);
						echo $time2;
						?></h3>
					</div>	
					
					<input type="radio" name="course-time" value="<?php echo $time; ?>" id="time_<?php echo $time; ?>">
					
				</div>
				<?php endforeach; ?>
			</div>	
			</label>
				<button class="prev-step"><i class="fa-solid fa-angle-left"></i>Quay lại</button>
				<button type="submit" class="next-step2">Kết quả <i class="fa-solid fa-angle-right"></i></button>
			
		</div>
		
	</div>
	<script>
jQuery(document).ready(function ($) {
    // Hàm xử lý việc chuyển bước
    function moveNextStep(currentStep) {
        // Ẩn bước hiện tại
        $('.form-step[data-step="' + currentStep + '"]').hide();
        
        // Hiển thị bước tiếp theo
        $('.form-step[data-step="' + (currentStep + 1) + '"]').show();
        
        // Nếu là bước cuối cùng, thực hiện submit form
        if (currentStep + 1 === 3) {
            // Gọi hàm submitForm để thực hiện submit form
            submitForm();
        }
    }

$(document).ready(function() {
    // Kiểm tra kích thước màn hình khi tải trang và cả khi thay đổi kích thước
    function handleScreenSize() {
        if ($(window).width() < 1167) {
            // Xử lý sự kiện khi click vào các radio button
            $('input[name="course-level"]').on('click', function () {
                moveNextStep(1);
            });

            $('input[name="course-goal"]').on('click', function () {
                moveNextStep(2);
            });

            // Lắng nghe sự kiện click cho radio button "course-time"
            $('input[name="course-time"]').on('click', function () {
                // Không cần thực hiện gì cả ở đây, chỉ để lắng nghe sự kiện
            });
        } else {
        }
    }

    // Gọi hàm khi trang tải và khi thay đổi kích thước màn hình
    handleScreenSize();
    $(window).resize(handleScreenSize);
});


// Lắng nghe sự kiện click cho nút "Kết quả"
document.querySelector('.next-step2').addEventListener('click', function () {
    // Gọi hàm submitForm để thực hiện submit form
    

    // Lấy phần tử overlay2
    var overlay2 = document.querySelector('.overlay2');
    
    // Lấy phần tử popup
    var popup = document.querySelector('.popupmb');
    
    // Xóa lớp CSS "show-overlay2" để ẩn overlay2
    overlay2.classList.remove('show-overlay2');

    // Xóa lớp CSS "show-popup" để ẩn popup
    popup.classList.remove('show-popup');
    $.scrollTo('#ketqua', {
        duration: 'slow', // Tốc độ cuộn (có thể là 'fast', 'slow' hoặc số mili giây)
        offset: -50 // Độ lệch từ đỉnh section
    });
	
	
	submitForm();
    // Chuyển đến link "#ketqua"
  
});



    // ... (Các đoạn mã JavaScript khác của bạn)

    // Hàm submitForm
    function submitForm() {
        // Gửi dữ liệu bằng AJAX
        $.ajax({
            type: 'POST',
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: {
                action: 'filter_courses',
                levels: selectedLevels,
                goal: selectedGoal,
                time: selectedTime,
            },
            success: function (response) {
                $('#course-results').html(response);
            }
        });
    }
});


</script>
	</div>
	<div class="res">
		<div id="course-results">
			<div id="result-image">
				<img src="/wp-content/themes/eduma-child/images/tm.png" alt="Image" />
				<p style="font-size: 16px">Bạn vui lòng chọn</p>
				<p style="font-size: 16px;color:#052FFF;font-weight:700">Trình độ hiện tại, Mục tiêu, Thời gian</p>
				<p style="font-size: 16px">để Zest tìm lộ trình học phù hợp cho bạn nhé</p>
		</div>

    </div>
    </div>
    </form>


    <?php
    return ob_get_clean();
}
add_shortcode('course_filter', 'display_course_filter_form');

// Ajax handler to filter courses
function filter_courses() {
	$selected_levels = $_POST['levels'];
	$selected_goal = $_POST['goal'];
	$selected_time = $_POST['time'];

	$args = array(
		'post_type' => 'courses',
		'tax_query' => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'course_level',
				'field' => 'slug',
				'terms' => $selected_levels,
			),
		),
		'orderby' => 'ID',
		'order' => 'ASC',
	);



	$courses = new WP_Query($args);

	ob_start();
	$count_courses = 0;


	if ($courses->have_posts()) {
		while ($courses->have_posts()) {
			$total_time = 0;
			$courses->the_post();
			$unique_popup_id = 'popup-' . get_the_ID();
			$course_time = get_post_meta(get_the_ID(), 'course_month', true);
			$thoigian = wp_get_post_terms(get_the_ID(), 'course_time');
			$bandau = wp_get_post_terms(get_the_ID(), 'course_level');
			$muctieu = wp_get_post_terms(get_the_ID(), 'course_goal');

			// Thiết lập biến để kiểm tra cả hai điều kiện
			$isTimeMatch = true; // Mặc định là true nếu không có thời gian được chọn

			if (!empty($thoigian)) {
				// Kiểm tra course_time
				$isTimeMatch = false;
				foreach ($thoigian as $valiiin) {
					if (($valiiin->name) === $_POST['time']) {
						$isTimeMatch = true;
						break;
					}
				}
			}
			
			if ($isTimeMatch){
				$count_courses++;
				
				$total_time += is_numeric($course_time) ? (int) $course_time : 0;
				}
				
			if ($isTimeMatch) 
			{ 
				 ?>
				<div class="flex-source">
					<div class="hnn--source">
						<h3 class="hnn--weight--700 hnn--size--28"><?php the_title(); ?></h3>  
						<p class="hnn--weight--500 hnn--size--14"><?php echo get_post_meta(get_the_ID(), 'course_summary', true); ?></p>
						
						<div id="overlay2" onclick="off()"></div>

						<a onclick="on()" class="hnn--color--052FFF hnn--weight--600 hnn--size--14 open-popup" data-popup-id="<?php echo $unique_popup_id; ?>" href="#">Xem chi tiết <i class="fa-solid fa-angle-down"></i></a>
					</div>
				</div>
				
				<div id="<?php echo $unique_popup_id; ?>" class="popup-box">
					<span class="close-btn" onclick="closePopup('<?php echo $unique_popup_id; ?>')">&#x2716;</span>
					<div class="text-mobile2">
					<p class="flex-source2 hnn--weight--600 hnn--size--24">Lộ trình phù hợp cho bạn</p>
					<p class="flex-source2 hnn--weight--600 hnn--size--16">Thời gian hoàn thành: <span id="displayLocation" class="hnn--color--052FFF"></span></p>
						</div>
						
						<?php
						$total_time2 = 0;
						$count_courses2 = 0;
						$all_courses = new WP_Query($args);
						if ($all_courses->have_posts()) {
							while ($all_courses->have_posts()) {
								$all_courses->the_post();
								$course_time2 = get_post_meta(get_the_ID(), 'course_month', true);
								$total_time2 += is_numeric($course_time2) ? (int) $course_time2 : 0;
								
								
								$unique_popup_idSS = 'popup-' . get_the_ID();
								$unique_popup_id2 = 'popup2-' . get_the_ID();
								$unique_popup_id3 = 'popup3-' . get_the_ID();
								
								$thoigian2 = wp_get_post_terms(get_the_ID(), 'course_time');
								$bandau2 = wp_get_post_terms(get_the_ID(), 'course_level');
								$muctieu2 = wp_get_post_terms(get_the_ID(), 'course_goal');
								
								
								$isTimeMatch2 = true; // Mặc định là true nếu không có thời gian được chọn

								if (!empty($thoigian2)) {
									// Kiểm tra course_time
									$isTimeMatch2 = false;
									foreach ($thoigian2 as $valiiin2) {
										if (($valiiin2->name) === $_POST['time']) {
											$isTimeMatch2 = true;
											break;
										}
									}
								}
								if ($isTimeMatch2) {$count_courses2++;?>
								<div class="tab-flex">
									<div class="tab">
										<div class="tablinks <?php if($unique_popup_idSS == $unique_popup_id){echo "active";} ?>" onclick="openTab(event, '<?php echo $unique_popup_id2; ?>')" >
											<h3 style="font-size: 20px"><?php the_title(); ?></h3>
											<p class="hnn--weight--500 hnn--size--14"><?php echo get_post_meta(get_the_ID(), 'course_summary', true); ?></p>
										</div>
									</div>

									<div id="<?php echo $unique_popup_id2; ?>" class="tabcontent" <?php if($unique_popup_idSS == $unique_popup_id){echo 'style="display: block"';} ?>>
										<div>
										<span class="close-btn zzz" onclick="closePopup('<?php echo $unique_popup_id; ?>')">&#x2716;</span>
										<h3 class="active2" style="font-size: 24px;color:#052FFF;font-weight:700"><?php the_title(); ?></h3>
										</div>
										<p><?php the_content(); ?></p>
										<a href="<?php echo get_post_meta(get_the_ID(), 'xemchitiet', true); ?>" class="_next-step">Chi tiết khoá học <i class="fa-solid fa-angle-right"></i></a>
									</div>
									<style>
									._next-step {
										font-size: 16px;
										text-transform: inherit;
										color: #fff;
										background: #052FFF;
										left: 0;
										float: left;
										border-radius: 12px;
										/* height: 48px; */
										padding: 12px 24px;
										max-height: 48px;
										font-weight: 600;
										margin-right: 0px !important;
									}
									</style>
									<!-- Thêm các tabcontent khác tương ứng với tablinks -->
								</div>		
								<?php  } 
							} 
						} 
						else  { echo 'Không có khoá học nào.'; } ?>
				</div>
			<?php }	
	}	
	
		echo '<p class="flex-source2 hnn--weight--600 hnn--size--16">Thời gian hoàn thành: <span  id="vitriso1" class="hnn--color--052FFF">' . $total_time2 . ' tháng - ' . ($count_courses2 - 1). ' khoá học</span></p>';
	} else {
		echo 'Hiện tại chưa có khoá học nèo bé ơi!.';
	}
	wp_reset_postdata();
	?>
    <script>
        // Lấy nội dung của phần tử có id="vitriso1"
        var contentVitriso1 = document.getElementById('vitriso1').innerHTML;

        // Gán nội dung của phần tử có id="vitriso1" vào vị trí muốn hiển thị
        document.getElementById('displayLocation').innerHTML = contentVitriso1;
    </script>

<style>
#overlay2 {
  position: fixed;
  display: none;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0,0,0,0.5);
  z-index: 51;
  cursor: pointer;
}
</style>
</head>



<script>
function on() {
  document.getElementById("overlay2").style.display = "block";
}

function off() {
  document.getElementById("overlay2").style.display = "none";
  document.getElementById("open-popup").style.display = "none";
}
function closePopup(popupId) {
  document.getElementById(popupId).style.display = 'none';
  document.getElementById('overlay2').style.display = 'none';
}


</script>

    <script>
		jQuery(document).ready(function($) {
			$('.open-popup').on('click', function(e) {
				e.preventDefault();
				var popupId = $(this).data('popup-id');
				$('.popup-box').hide(); // Ẩn tất cả các popup trước khi hiển thị popup cần thiết
				$('#' + popupId).show(); // Hiển thị popup có ID tương ứng khi click
			});
			  $('#overlay2').on('click', function() {
				$('.popup-box').hide();
				$(this).hide();
			  });
			// Đóng popup khi click ra ngoài phần nội dung popup
			$(document).mouseup(function(e) {
				var popupContainer = $('.popup-box');
				
				// Nếu click không nằm trong phần nội dung của popup, đóng popup
				if (!popupContainer.is(e.target) && popupContainer.has(e.target).length === 0) {
					popupContainer.hide();
				}
			});
		});

    </script>

	
  <script>
function openTab(event, tabName) {
    var tablinks = document.getElementsByClassName('tablinks');
    var tabContents = document.getElementsByClassName('tabcontent');

    // Lặp qua tất cả các tablinks
    for (var i = 0; i < tablinks.length; i++) {
        if (tablinks[i] === event.currentTarget) {
            // Nếu tablink được click khớp với tablinks[i], kiểm tra xem đã có class 'active' hay chưa
            if (!tablinks[i].classList.contains('active')) {
                // Nếu chưa có, thêm class 'active' và hiển thị tab content tương ứng
                tabContents[i].style.display = 'block';
                tablinks[i].classList.add('active');
            }
        } else {
            // Ẩn tab content không được chọn và loại bỏ lớp 'active'
            tabContents[i].style.display = 'none';
            tablinks[i].classList.remove('active');
        }
    }
}



</script>


	<?php

	

    wp_reset_postdata();

    $response = ob_get_clean();
    wp_send_json($response);
    exit();
}
add_action('wp_ajax_filter_courses', 'filter_courses');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses');


// short_code banner header
function display_header_banner(){
    ob_start();
    ?>
     <style>
        *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        
    }
    :root{
    --txt-color-h: #111827;
    --bg-primary: #052FFF;
    --bg-seconday: #ffffff;
    --bg-threeday: #F0F5FF;
    --fw-500: 500;
    --fw-600: 600;
    --fw-700: 700;
    --fw-800: 800;
  
    }
    body{
        background-color: var(--bg-threeday);
        font-family: 'Inter', sans-serif;
    }

/* header-banner */
    .header-banner{
      background-color: var(--bg-seconday);
      width: 100%;
      height: 794px;
      /* background-image: url(./assets/images/Banner-image.png); */
      object-fit: cover;
      background-repeat: no-repeat;
      background-position: center center; /* Căn giữa theo chiều ngang và chiều dọc */
      background-size: cover; /* Hiển thị toàn bộ background-image và căn giữa */
    }
    .container-header-mobile{
        display: none;
    }
    /* hieu ung cho icon con chuot d/c */
    .icon3d {
        z-index: 2;
        position: absolute;
        cursor: pointer;
        transform-style: preserve-3d;
        transition: all .1s;
    }
    /* end hieu ung cho icon con chuot d/c */
    .icon-top-left{
        top: 125px;
        left: 227px;
        display: inline;
        position: absolute;
        cursor: pointer;
    }
    .icon-top-left .imgtl{
        width: 89px;
        height: 89px;
        flex-shrink: 0;
    }
    .icon-top-left-bottom-center{
        width: 48px;
        height: 48px;
        top: 392px;
        left: 32px;
        display: inline;
        position: absolute;
        cursor: pointer;
    }
    .icon-top-left-bottom-center .imgtlbc{
        width: 48px;
        height: 48px;
        flex-shrink: 0;
    }
    .icon-top-left-bottom{
        width: 54px;
        height: 54px;
        top: 656px;
        left: 176px;
        display: inline;
        position: absolute;
        cursor: pointer;
    }
    .icon-top-left-bottom .imgtlb{
        width: 54px;
        height: 54px;
        flex-shrink: 0;
    }
    .icon-top-right{
        z-index: 1;
        width: 62px;
        height: 62px;
        top: 230px;
        right: 279px;
        display: inline;
        position: absolute;
        cursor: pointer;
    }
    .icon-top-right .imgtr{
        width: 62px;
        height: 62px;
        flex-shrink: 0;
    }
    .icon-top-right-bottom-center{
        width: 32px;
        height: 32px;
        top: 275px;
        right: 15px;
        display: inline;
        position: absolute;
        cursor: pointer;
    }
    .icon-top-right-bottom-center .imgtrbc{
        width: 32px;
        height: 32px;
        flex-shrink: 0;
    }
    .icon-top-right-bottom-center2x{
        width: 32px;
        height: 32px;
        top: 493px;
        right: 302px;
        display: inline;
        position: absolute;
        cursor: pointer;
    }
    .icon-top-right-bottom-center2x .imgtrbc2x{
        width: 32px;
        height: 32px;
        flex-shrink: 0;
    }
    .icon-top-right-bottom{
        width: 91.349px;
        height: 60.408px;
        top: 597.28px;
        right: 69.21px;
        display: inline;
        position: absolute;
        cursor: pointer;
    }
    .icon-top-right-bottom .imgtrb{
        width: 91.349px;
        height: 60.408px;
        flex-shrink: 0;
    }
    .header-banner img{
        width: 100%;
        object-fit: cover;
    }
    .header-banner-title{
        margin-top: 48px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .header-banner-title h1{
       margin-bottom: 0 !important;
       margin-top: 0 !important;
       font-weight: var(--fw-700);
       font-size: 64px;
       text-align: center;
       line-height: 76.8px;
    }
    .header-banner-title h2{
       margin-top: 0 !important;
       margin-bottom: 0 !important;
       overflow: hidden;
       /* animation: typing 2s steps(22), blink .5s step-end infinite alternate; */
       font-weight: var(--fw-700);
       font-size: 64px;
       text-align: center;
       color: var(--bg-primary);
       line-height: 76.8px;
    }
    .typed-cursor,.typed-cursor--blink{
        display: none;
    }
    .header-banner-title p{
        z-index: 2;
        font-size: 14px;
        width: 673px;
        margin-top: 24px;
        line-height: 21px;
    }
    .container-zfe-bottom-banner{
        max-width: 1140px;
        margin: 0 auto;
    }
    .container-content{
        max-width: 796px;
        margin: 0 auto;
    }
    a{
        text-decoration: none;
    }
    .header-banner-button{
        position: relative;
        z-index: 999;
        margin-top: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
    }
    a.btn-zfe{
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bg-seconday);
        padding: 12px 16px;
        border-radius: 12px;
        background-color: var(--bg-primary);
    }
    a.btn-border{
        color: var(--bg-primary);
        border: 1px solid var(--bg-primary);
        border-radius: 12px;
        padding: 12px 16px;
        background-color: var(--bg-seconday);
    }
   
    a.btn-zfe:hover{
        color: var(--bg-primary);
        border: 1px solid var(--bg-primary);
        border-radius: 12px;
        background-color: var(--bg-seconday);
    }
    a.btn-border:hover{
        color: var(--bg-seconday);
        background-color: var(--bg-primary);
    }
    
    .header-banner-center{
        z-index: 1;
        background: linear-gradient(180deg,
        rgba(240, 245, 255, 0.00) 0%, #F0F5FF 77.53%);
        /*max-height: 504px;*/
        position: relative;
        align-items: center;
        display: flex;
        flex-direction: column;
        /*max-width: 1440px;*/
        margin: 0 auto;
        /*overflow: hidden;*/
    }
    .header-banner-center .wawe-top{
        position: absolute;
        top: -125px;
        transform-origin: 50% 50%;
        -webkit-transition: transform 0.2s ease-out;
        -moz-transition: transform 0.2s ease-out;
        -o-transition: transform 0.2s ease-out;  
        stroke-dasharray: 4 4;
        stroke-dashoffset: 0;
        stroke: #FEC200;
        animation: waveAnimation 2s cubic-bezier(.55, .5, .45, .5) infinite;
    }
    .header-banner-center .wawe-center{
        top: -40px;
        position: relative;
        transform-origin: 50% 50%;
        -moz-transition: transform 0.2s ease-out;
        -o-transition: transform 0.2s ease-out; 
        stroke-dasharray: 4 4; 
        stroke-dashoffset: 0;
        stroke: #052FFF;
        animation: waveAnimation 2s cubic-bezier(.55, .5, .45, .5) infinite;
    }
    .header-banner-center .wawe-bottom{
        /* transform: rotateY(17.7deg); */
        top: -125px;
        position: relative;
        transform-origin: 50% 50%;
        -webkit-transition: transform 0.2s ease-out;
        -moz-transition: transform 0.2s ease-out;
        -o-transition: transform 0.2s ease-out; 
        stroke-dasharray: 4 4;
        stroke-dashoffset: 0;
        stroke: #052FFF;
        animation: waveAnimation 2s cubic-bezier(.55, .5, .45, .5) infinite;
    } 
    @keyframes waveAnimation {
            0% {
                stroke-dashoffset: 0;
            }
            100% {
                stroke-dashoffset: 200;
            }
    }
   
    .header-banner-center .img-header-banner-center{
        position: absolute;
        overflow: hidden;
        top: 440px;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 569px;
        height: auto;
        /* max-height: 569px; */
    }

    .header-banner-bottom-container-bg{
        position: relative;
        z-index: 1;
        width: 100%;
        background-color: var(--bg-threeday);
    }
    .header-banner-bottom-bg{
        color: var(--bg-seconday);
        border-radius: 24px;
        margin: 0 auto;
        gap: 32px;
        padding: 48px;
        background-color: #0320AF;
        display: flex;
        max-width: 1140px;
        justify-content: flex-start;
        align-items: center;
        padding-bottom: 48px;
    }
    .header-banner-container{
        width: 327px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    /* hieu ung fadeinup */

    /* end hieu ung fadeinup */

    
    .header-banner-bottom{
        display: flex;
        gap: 4px;
    }
    .header-banner-bottom-bg .text-counter{
        font-weight: var(--fw-700);
        font-size: 36px;
    }
    .header-banner-bottom-bg .text-counter-after{
        font-weight: var(--fw-700);
        font-size: 36px;
    }
    .header-banner-bottom-end .title-counter{
        text-align: center;
        font-size: 16px;
        font-weight: 600;
        line-height: 24px;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .header-banner-bottom-end .title-counter.fade-in-up {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateY(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }

/* end header-banner */


/* reponsive for mobile */
@media screen and (max-width: 768px) {
        body{
            margin: 0 auto;
            /* padding: 0 16px; */
        }
        .container-zfe{
            margin: 0 auto;
            width: 100%;
        }
        .header-banner{
            background-image: none;
            height: 100%;
            background-color: var(--bg-seconday);
            padding: 48px 16px;
        }
        .icon-top-left,.icon-top-left-bottom-center,
        .icon-top-left-bottom,.icon-top-right,
        .icon-top-right-bottom-center,
        .icon-top-right-bottom-center2x,
        .icon-top-right-bottom,
        .header-banner-center{
            display: none;
        }
        .container-header-mobile{
            display: inline-block;
            position: relative;
        }
        .container-header-mobile img{
            margin: auto;
            width: 100%;
            height: auto;
        }
        .header-banner-title{
            margin-top: 32px;
        }
        
        .header-banner-title h1{
            font-size: 24px;
            text-align: center;
            line-height: normal;
        }
        .header-banner-title h2{
            font-size: 24px;
            text-align: center;
            line-height: normal;
        }
        .header-banner-title p{
            width: 100%;
            font-size: 14px;
            line-height: 19.6px;
        }
        .header-banner-button{
            flex-direction: column;
            width: 100%;
        }
        .btn-zfe{
            width: 100%;
        }
        .header-banner-button .btn-zfe{
            /*width: 100%;*/
            font-size: 16px;
            font-weight: var(--fw-600);
            line-height: 24px;
            /* max-width: 343px; */
        }
        .header-banner-button .btn-zfe:hover{
            color: var(--bg-primary);
            border: 1px solid var(--bg-primary);
            border-radius: 12px;
            background-color: var(--bg-seconday);
        }
        .header-banner-bottom-container-bg{
            width: 100%;
            padding: 48px 16px;
            background-color: var(--bg-seconday);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .header-banner-bottom-bg{
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .header-banner-bottom-bg .text-counter{
            font-size: 30px;
        }
        .header-banner-bottom-bg .text-counter-after{
            font-size: 30px;
        }
        .header-banner-bottom-end .title-counter{
            font-size: 14px;
            font-weight: 400;
        }
        .header-banner-bottom-end p{
            font-size: 14px;
            font-weight: 400;
        }
    }
    </style>
    <section class="header-banner">
        <div class="container-zfe">
            <div class="container-content">
                <div class="container-header-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/image-top-banner-mobile.png" alt="">
                </div>
                <div class="icon3d icon-top-left">
                    <img class="imgtl" src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-top-left.png" alt="">
                </div>
                <div class="icon3d icon-top-left-bottom-center">
                    <img class="imgtlbc" src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-center-right-content.png" alt="">
                </div>
                <div class="icon3d icon-top-left-bottom">
                    <img class="imgtlb" src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bottom-left.png" alt="">
                </div>

                <div class="icon3d icon-top-right">
                    <img class="imgtr" src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-top-right-content.png" alt="">
                </div>
                <div class="icon3d icon-top-right-bottom-center">
                    <img class="imgtrbc" src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-center-right.png" alt="">
                </div>
                <div class="icon3d icon-top-right-bottom-center2x">
                    <img class="imgtrbc2x" src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-center-right-content.png" alt="">
                </div>
                <div class="icon3d icon-top-right-bottom">
                    <img class="imgtrb" src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bottom-right.png" alt="">
                </div>
                
                <div class="header-banner-title">
                    <h1>Chinh Phục IELTS Bằng</h1>
                    <h2></h2>
                    <p>Với phương châm “Học thật, 
                    dùng thật”, Zest cam kết giúp mọi học chạm đến điểm 
                    IELTS mơ ước thông qua việc hình thành tư duy ngôn ngữ</p>
                </div>
                <div class="header-banner-button">
                    <a href="#" class="btn-zfe">
                        Đăng ký trải nghiệm Z-extra
                    </a>
                    <a href="#" class="btn-zfe btn-border">
                        Nhận tư vấn
                    </a>
                </div>
            </div>
        </div>
        <div class="header-banner-center">
            <svg class="wawe-top" xmlns="http://www.w3.org/2000/svg" width="1440"  height="441" viewBox="0 0 1440 441" fill="none">
                <path d="M1707.55 440.16C1301.39 65.4955 1010.29 505.501 552.423 396.966C94.5567 288.432 -142.768 1.55284 -142.768 1.55284" stroke="#FEC200" stroke-dasharray="4 4"/>
            </svg>
            <svg class="wawe-center"xmlns="http://www.w3.org/2000/svg" width="1440" height="345" viewBox="0 0 1440 345" fill="none">
                <path d="M1700.59 91.8074C1218.97 -211.846 1037.2 343.362 566.65 343.362C96.0952 343.362 -201 91.8078 -201 91.8078" stroke="#052FFF" stroke-dasharray="4 4"/>
            </svg>
            <svg class="wawe-bottom" xmlns="http://www.w3.org/2000/svg" width="1440" height="287" viewBox="0 0 1440 287" fill="none">
                <path d="M1674.25 1.34398C1252.78 449.371 742.859 -22.7746 243.5 131.5C-255.859 285.774 -298 603.501 -298 603.501" stroke="#052FFF" stroke-dasharray="4 4"/>
            </svg>
            <img class="img-header-banner-center" src="https://zestforenglish.vn/wp-content/uploads/2023/12/bg-zfe-resize-new.png" alt="">
        </div>
    </section>
    <section class="header-banner-bottom-container-bg">
        <div class="header-banner-bottom-bg container-zfe-bottom-banner">
            <div class="header-banner-container">
                <div class="header-banner-bottom">
                    <span class="text-counter" data-val="06">00</span><span class="text-counter-after"> </span>
                    <span class="icon-counter"><img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bgo.png" alt=""></span>
                </div>
                <div class="header-banner-bottom-end">
                    <p class="title-counter fade-all">năm nghiên cứu và phát triển</p>
                </div>
            </div> 
            <div class="header-banner-container">
                <div class="header-banner-bottom">
                    <span class="text-counter" data-val="50">00</span><span class="text-counter-after">+</span>
                    <span class="icon-counter"><img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bgo.png" alt=""></span>
                </div>
                <div class="header-banner-bottom-end">
                    <p class="title-counter fade-all">Khóa học đã diễn ra</p>
                </div>
            </div> 
            <div class="header-banner-container">
                <div class="header-banner-bottom">
                    <span class="text-counter" data-val="100">00</span><span class="text-counter-after">%</span>
                    <span class="icon-counter"><img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bgo.png" alt=""></span>
                </div>
                <div class="header-banner-bottom-end">
                    <p class="title-counter fade-all">học viên đạt mục tiêu</p>
                </div>
            </div> 
        </div>
    </section>
<script>
        
document.addEventListener('DOMContentLoaded', function () {

// Thêm hiệu ứng typing cho tiêu đề h2
var typing = new Typed(".header-banner-title h2", {
    strings: ["Tư Duy Ngôn Ngữ"],
    typeSpeed: 100,
    backSpeed: 40,
    loop: false,
});

// Xử lý hiệu ứng đếm số khi cuộn đến phần tử
let valueDisplays = document.querySelectorAll(".text-counter");
let interval = 1000;

valueDisplays.forEach((valueDisplay) => {
            valueDisplay.textContent = "00";
        });

function checkVisibility() {
    let bannerBottom = document.querySelector(".header-banner-bottom-container-bg");
    let rect = bannerBottom.getBoundingClientRect();
    

    // Nếu phần tử xuất hiện trong tầm nhìn, kích hoạt đếm số
    if (rect.top <= window.innerHeight && rect.bottom >= 0) {
        valueDisplays.forEach((valueDisplay) => {
            if (!valueDisplay.dataset.counted) {
                let startValue = 00;
                let endValue = parseInt(valueDisplay.getAttribute("data-val"));
                let duration = Math.floor(interval / endValue);
                
                valueDisplay.textContent = startValue.toString().padStart(2, '0');
                
                //setInterval để cập nhật giá trị đếm
                let counter = setInterval(function () {
                    startValue += 1;
                    valueDisplay.textContent = startValue.toString().padStart(2, '0');

                    // Khi giá trị đếm đạt giá trị cuối cùng, dừng đếm
                    if (startValue === endValue) {
                        clearInterval(counter);
                        valueDisplay.dataset.counted = "true"; // Đánh dấu đã đếm để tránh đếm lại
                    }
                }, duration);
            }
        });
    }
}

// Gọi hàm kiểm tra khi cuộn hoặc khi trang tải
window.addEventListener('scroll', checkVisibility);
window.addEventListener('load', checkVisibility);
});


//xử lý di chuyển chuột
const elements = document.querySelectorAll('.icon3d');

// Theo dõi trạng thái di chuyển chuột
let isMouseMoving = false;

// Lưu thông tin về sự kiện chuột cuối cùng
let lastMouseEvent;

// Lưu thời điểm cuối khi di chuyển chuột
let lastMouseMoveTime = Date.now();


function moveElements() {
    if (isMouseMoving && lastMouseEvent) {
        // Nếu chuột đang di chuyển và đã có sự kiện di chuyển chuột trước đó
        elements.forEach(element => {
            let box = element.getBoundingClientRect();
            let calcX = (lastMouseEvent.clientX - box.x - (box.width / 2)) / 10;
            let calcY = (lastMouseEvent.clientY - box.y - (box.height / 2)) / 10;

            // Thiết lập hiệu ứng chuyển động cho phần tử và áp dụng 'translate3d' để di chuyển mượt mà
            element.style.transition = 'transform 0.3s ease-out';
            element.style.transform = `translate3d(${calcX}px, ${calcY}px, 0)`;
        });
    } else if (!isMouseMoving) {
        // Nếu không di chuyển chuột
        const currentTime = Date.now();
        const elapsedTime = currentTime - lastMouseMoveTime;

        if (elapsedTime > 5000) {
            // Lớn hơn 5s thì cho icon về vị trí gốc
            elements.forEach(element => {
                element.style.transition = 'transform 0.3s ease-out';
                element.style.transform = 'translate3d(0, 0, 0)';
            });
        }
    }

    // Gọi lại hàm 'moveElements' để chuyển động mượt mà cho chuột và icon 
    requestAnimationFrame(moveElements);
}

// Lắng nghe sự kiện di chuyển chuột
document.addEventListener('mousemove', (event) => {
    // Đặt 'isMouseMoving' là 'true'
    isMouseMoving = true;

    // Lưu sự kiện chuột cuối cùng v
    lastMouseEvent = event;

    // Cập nhật thời gian chuột chuyển động cuối cùng
    lastMouseMoveTime = Date.now();

    // Sau 50ms thì tạo giả chuột
    setTimeout(() => {
        isMouseMoving = false;
    }, 50);
});

moveElements();

</script>
    <?php
    return ob_get_clean();
}
add_shortcode('header-banner','display_header_banner');
// end short_code banner header


// short_code metric
function display_metric_zestforenglish(){
    ob_start();
    ?>
     <style>
         *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        
    }
    :root{
    --txt-color-h: #111827;
    --bg-primary: #052FFF;
    --bg-seconday: #ffffff;
    --bg-threeday: #F0F5FF;
    --fw-500: 500;
    --fw-600: 600;
    --fw-700: 700;
    --fw-800: 800;
  
    }
    body{
        background-color: var(--bg-threeday);
        font-family: 'Inter', sans-serif;
    }

/* metric */
.container-zfe{
        max-width: 1140px;
        margin: 0 auto;
    }
    .container-content{
        max-width: 796px;
        margin: 0 auto;
    }
    a{
        text-decoration: none;
    }
.header-top-metric {
        padding-bottom: 48px;
        /* background-color: var(--bg-seconday); */
        width: 100%;
        opacity: 0;
        transform: translateY(40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .header-top-metric-title{
        padding-top: 76px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .header-top-metric.fade-in-up {
      opacity: 1;
      transform: translateY(0);
      transition-delay: 0.3s;
    }
    .header-top-metric-title h2{
        margin-top: 0px !important;
        margin-bottom: 0px !important;
        font-size: 64px;
        font-weight: var(--fw-700);
        color: var(--bg-primary);
    }
    .typed-cursor,.typed-cursor--blink{
        display: none;
    }
    .header-top-metric-title b{
        font-size: 24px;
        color: var(--txt-color-h);
        font-weight: var(--fw-700);
    }
    .header-top-metric-title p{
        margin-top: 24px !important;
        font-size: 14px;
        color: var(--txt-color-h);
    }
    .header-top-metric-btn{
        margin-top: 32px !important;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .header-top-metric-btn.fade-in-up {
        margin-top: 32px !important;
        transition-delay: 0.3s;
        opacity: 1;
        transform: translateY(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }
    }
    .btn-metric{
        cursor: pointer;
    }
    /* .btn-metric-active:hover{
       color: #fec200;
    } */

    .btn-active{
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        background-color: var(--bg-primary);
        border-radius: 100px;
        font-size: 14px;
        font-weight: var(--fw-600);
        color: var(--bg-seconday);
    }
    .tab-active{
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        background-color: var(--bg-primary);
        border-radius: 100px;
        font-size: 14px;
        font-weight: var(--fw-600);
        color: var(--bg-seconday);
    }
    .tab-active-course{
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        background-color: var(--bg-primary);
        border-radius: 100px;
        font-size: 14px;
        font-weight: var(--fw-600);
        color: var(--bg-seconday);
    }
   
    .btn-non-active{
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        /* padding: 12px 24px; */
        background-color: none;
        border-radius: 24px;
        font-size: 14px;
        font-weight: var(--fw-600);
        /* color: var(--txt-color-h); */
    }

    .btn-metric-course{
        cursor: pointer;
    }
   
    .center{
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .content-metric{
        margin-top: 24px !important;
        background-color: var(--bg-seconday);
        padding: 32px;
        border-radius: 24px;
        display: none;
        flex-direction: column;
        overflow: hidden;
        -webkit-animation: fadeEffect 1s;
        animation: fadeEffect 1s;
    }
    /* Fade in tabs */
    @-webkit-keyframes fadeEffect {
    from {opacity: 0;}
    to {opacity: 1;}
    }

    @keyframes fadeEffect {
    from {opacity: 0;}
    to {opacity: 1;}
    }
    #content-metric-h4{
        margin: 0 !important;
    }
    .content-metric h4{
        text-align: center;
        color: var(--txt-color-h);
        font-size: 24px;
        font-weight: var(--fw-700);
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .content-metric h4.fade-in-right {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }
    .metric-title-in-color{
        color: var(--bg-primary);
    }
    .content-metric-container-in{
        gap: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 32px !important;
    }
    .content-metric-export{
        transition: transform .2s;
        height: auto;
        display: flex;
        background-color: var(--bg-seconday);
        border-radius: 24px;
        padding: 16px;
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        /* gap: 16px; */
        /* width: 167px; */
    }
    /* hieu ung zoom */
    .zoom:hover {
         cursor: pointer;
        -ms-transform: scale(1.10); /* IE 9 */
        -webkit-transform: scale(1.10); /* Safari 3-8 */
        transform: scale(1.10); 
    }
    /*end hieu ung zoom */
    .content-metric-export.fade-in-right {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }

    .content-metric-export-top{
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 16px;
    }
    .content-metric-export img{
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .content-metric-export p{
        font-weight: 400;
        font-size: 16px;
        text-align: center;
        line-height: 24px;
        color: var(--txt-color-h);
        font-style: normal;
    }
    .metric-title-in{
        margin-top: 48px;
    }
    .metric-title-in-bottom{
        margin-top: 56px;
        color: var(--bg-primary) !important;
    }
    .metric-title-in-bottom-text{
        font-weight: 400;
        color: #111827;
        font-size: 14px;
        text-align: center;
        margin-top: 8px;
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .metric-title-in-bottom-text.fade-in-right {
        transition-delay: 0.3s;
        opacity: 1;
        transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }
    .content-metric .metric-image{
        max-width: 895px;
        max-height: 412px;
        margin-top: 20px;
        display: block;
        margin-left: auto;
        margin-right: auto;

    }
    .content-metric .metric-image-container{
        position: relative;
        margin-top: 20px;
        display: flex;
        /* align-items: center; */
        justify-content: center;
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .metric-image-container.fade-in-right {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }
    .content-metric .metric-image-container img{
        width: 376px;
        height: 376px;
    }
    .metric-top-content{
        justify-content: space-between;
        display: flex;
        margin-top: 0px;
        position: absolute;
        width: 600px;
        height: 84px;
        /* background-color: rebeccapurple; */
    }
    .metric-top-content .metric-top-content-btn-left{
        -webkit-transition: margin 0.5s ease-out;
        -moz-transition: margin 0.5s ease-out;
        -o-transition: margin 0.5s ease-out;  /* Float On hover*/
        margin-top: 28px;
        position: absolute;
        display: flex;
        align-items: center;
        /* justify-content: center; */
        gap: 12px;
        width: 245px;
        height: 56px;
        background-color: #fff;
        border-radius: 56px;
        border: 1px solid#E5E7EB;
        padding: 8px 16px;
        box-shadow: 0px 10px 20px 0px rgba(0, 46, 78, 0.10);
    }
    /* hieu ung Float On hover*/
    .metric-top-content .metric-top-content-btn-left:hover{
        margin-top: 5px;
    }
    /* end hieu ung Float On hover*/
    .metric-top-content .metric-top-content-btn-left img{
        width: 28px;
        height: 28px;
    }
    .metric-top-content .metric-top-content-btn-left .metric-top-content-btn-left-ex{
        font-size: 14px;
        color: #00074D;
        font-weight: 400;
        line-height: 19.6px;
    }
    .metric-top-content .metric-top-content-btn-right{
        -webkit-transition: margin 0.5s ease-out;
        -moz-transition: margin 0.5s ease-out;
        -o-transition: margin 0.5s ease-out;  /* Float On hover*/
        display: flex;
        position: absolute;
        right: 30px;
        align-items: center;
        /* justify-content: center; */
        gap: 12px;
        width: 194px;
        height: 56px;
        background-color: #fff;
        border-radius: 56px;
        border: 1px solid#E5E7EB;
        padding: 8px 16px;
        box-shadow: 0px 10px 20px 0px rgba(0, 46, 78, 0.10);
    }
     /* hieu ung Float On hover*/
     .metric-top-content .metric-top-content-btn-right:hover{
        margin-top: -15px;
    }
    .metric-top-content .metric-top-content-btn-right img{
        width: 28px;
        height: 28px;
    }
    .metric-top-content .metric-top-content-btn-right .metric-top-content-btn-right-ex{
        font-size: 14px;
        color: #00074D;
        font-weight: 400;
        line-height: 19.6px;
        align-items: center;
    }
    
    .metric-center-content{
        justify-content: space-between;
        display: flex;
        margin-top: 199px;
        position: absolute;
        width: 895px;
        height: 100px;
        /* background-color: rebeccapurple; */
    }
    .metric-center-content .metric-center-content-btn-left{
        -webkit-transition: margin 0.5s ease-out;
        -moz-transition: margin 0.5s ease-out;
        -o-transition: margin 0.5s ease-out;  /* Float On hover*/
        position: absolute;
        margin-top: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 345px;
        height: 56px;
        padding: 8px 16px;
        border-radius: 56px;
        border: 1px solid #E5E7EB;
        background:  #FFF;
        box-shadow: 0px 10px 20px 0px rgba(0, 46, 78, 0.10);
    }
    /* hieu ung Float On hover*/
    .metric-center-content .metric-center-content-btn-left:hover{
        margin-top: 15px;
    }
    .metric-center-content .metric-center-content-btn-left img{
        width: 28px;
        height: 28px;
    }
    .metric-center-content .metric-center-content-btn-left .metric-center-content-btn-left-ex{
        font-size: 14px;
        color: #00074D;
        font-weight: 400;
        line-height: 19.6px;
    }
    .metric-center-content .metric-center-content-btn-right{
        -webkit-transition: margin 0.5s ease-out;
        -moz-transition: margin 0.5s ease-out;
        -o-transition: margin 0.5s ease-out;  /* Float On hover*/
        display: flex;
        position: absolute;
        top:0;
        right: 30px;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 261px;
        height: 56px;
        padding: 8px 16px;
        border-radius: 56px;
        border: 1px solid #E5E7EB;
        background:  #FFF;
        box-shadow: 0px 10px 20px 0px rgba(0, 46, 78, 0.10);
    }
    /* hieu ung Float On hover*/
    .metric-center-content .metric-center-content-btn-right:hover{
        margin-top: -15px;
    }
    .metric-center-content .metric-center-content-btn-right img{
        width: 28px;
        height: 28px;
    }
    .metric-center-content .metric-center-content-btn-right-ex{
        font-size: 14px;
        color: #00074D;
        font-weight: 400;
        line-height: 19.6px;
    }

    .metric-bottom-content {
        justify-content: space-between;
        display: flex;
        margin-top: 356px;
        position: absolute;
        width: 895px;
        height: 56px;
        /* background-color: rebeccapurple; */
    }
    .metric-bottom-content .metric-bottom-content-btn-right{
        -webkit-transition: margin 0.5s ease-out;
        -moz-transition: margin 0.5s ease-out;
        -o-transition: margin 0.5s ease-out;  /* Float On hover*/
        position: absolute;
        top: 0;
        left: 476px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 230px;
        height: 56px;
        padding: 8px 16px;
        border-radius: 56px;
        border: 1px solid #E5E7EB;
        background: #FFF;
        box-shadow: 0px 10px 20px 0px rgba(0, 46, 78, 0.10);
    }
     /* hieu ung Float On hover*/
    .metric-bottom-content .metric-bottom-content-btn-right:hover{
        margin-top: -15px;
    }
    .metric-bottom-content .metric-bottom-content-btn-right img{
        width: 28px;
        height: 28px;
    }
    .metric-bottom-content .metric-bottom-content-btn-right .metric-bottom-content-btn-right-ex{
        font-size: 14px;
        color: #00074D;
        font-weight: 400;
        line-height: 19.6px;
    }

    .metric-content-mobile{
        display: none;
    }
     .content-metric-skill .content-metric-skill-title{
        opacity: 1;
        font-size: 24px;
        color: var(--bg-primary);
        text-align: center;
        margin-top: 86px;
        font-weight: var(--fw-700);
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .content-metric-skill .content-metric-skill-title.fade-in-right {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }
    .content-metric-container-in-skill{
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }
    .content-metric-export-skill{
        display: flex;
        flex-direction: column;
        border-radius: 24px;
        padding: 24px;
        border: 1px solid #E5E7EB;
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .content-metric-export-skill.fade-in-right {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }
    .content-metric-export-top-skill{
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .content-metric-export-top-skill img{
        width: 64px;
        height: 64px;
    }
    .content-metric-export-top-skill h6{
        font-size: 20px;
        font-weight: var(--fw-600);
    }
    .content-metric-export-top-skill p{
        font-weight: 400;
        font-size: 14px;
        text-align: center;
        line-height: 19.6px;
        color: #111827;
    }
    .content-metric.tab-active{
        display: block;
    }


    .course{
        background-color: var(--bg-seconday);
        padding: 48px 0px;
    }
    .course-title h3{
        font-size: 36px;
        font-weight: var(--fw-700);
        color: var(--txt-color-h);
        text-align: center;
        opacity: 0;
        transform: translateX(-80px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .course-title h3.fade-in-right {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }
    .course-title span{
        font-size: 36px;
        font-weight: var(--fw-700);
        color: var(--bg-primary);
    }
    .header-top-metric-btn-mb{
        gap: 16px;
        margin-top: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        /* overflow-x: scroll; */
        opacity: 0;
        transform: translateY(40px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .header-top-metric-btn-mb.fade-in-up {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateY(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
    }

/* end smetric */

/* reponsive for mobile */
@media screen and (max-width: 768px) {
        .container-zfe{
            margin: 0 auto;
            width: 100%;
        }
        .btn{
            width: 100%;
        }
        
        .header-top-metric{
            padding: 48px 16px;
            background-color: var(--bg-threeday);
        }
        .header-top-metric-title{
            padding-top: 0px;
            margin: 0;
        }
        .header-top-metric-title h2{
            font-weight: var(--fw-700);
            line-height: 45px;
            font-size: 30px;
            text-align: center;;
        }
        .header-top-metric-title b{
            font-size: 20px;
            text-align: center;
            font-weight: var(--fw-700);
            line-height: 28px;
        }
        .header-top-metric-title p{
            font-size: 14px;
            font-weight: 400;
            line-height: 19.6px;
            text-align: center;
        }
        .header-top-metric-btn.fade-in-up{
            margin-top: 24px !important;
        }
        .header-top-metric-btn{
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            overflow-x: scroll;
        }

        .content-metric{
            margin-top: 16px;
            padding: 16px;
        }
        .content-metric h4{
            margin: 0px;
            line-height: 28px;
            font-size: 20px;
            font-weight: var(--fw-700);
        }
        .content-metric-container-in{
            margin-bottom: 48px;
            padding: 0;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            /* flex-direction: column; */
            align-items: center;
            justify-content: center;
        }
        .content-metric-export{
            max-width: 139.5px;
            width: 100%;
            box-sizing: border-box;
            padding: 16px;
        }
        #metric-id-mobile{
            width: 100%;
        }
        .metric-title-in-color{
            color: var(--bg-primary);
        }
        .metric-title-in-bottom{
            margin-top: 24px;
            font-size: 20px;
            color: var(--bg-primary);
            line-height: 28px;
        }
        #metric-title-in{
            font-size: 24px !important;
            font-style: normal !important;
            font-weight: 700 !important; 
            line-height: normal !important;
        }
        #metric-title-in-bottom{
            margin-top: 24px !important;
            font-size: 20px !important;
            color: var(--bg-primary)!important;
            line-height: 28px !important;
        }
        .metric-title-in-bottom-text{
            margin-top: 8px !important;
            line-height: 19.6px !important;
        }
        .content-metric .metric-image{
            /* width: 100%;
            height: auto; */
            display: none;
        }
        .content-metric .metric-image-container{
            display: none;
        }
        .metric-content-mobile{
            align-items: center;
            justify-content: center;
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: translateX(-40px);
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        .metric-content-mobile.fade-in-right {
            transition-delay: 0.3s;
            opacity: 1;
            transform: translateX(0); /* Di chuyển về vị trí ban đầu (không di chuyển) */
        }
        .metric-content-mobile .metric-content-mobile-img{
            width: 100%;
            /* height: auto; */
            max-width: 240px;
            max-height: 240px;
            margin-top: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .metric-content-mobile .metric-content-text-mobile{
            margin-top: 8px;
            display: flex;
            width: 279px;
            min-height: 56px;
            max-height: 76px;
            padding: 8px 16px;
            /* justify-content: center; */
            align-items: center;
            gap: 12px;
            border-radius: 56px;
            border: 1px solid #E5E7EB;
            background-color: var(--bg-seconday);
            box-shadow: 0px 10px 20px 0px rgba(0, 46, 78, 0.10);
        }
        .metric-content-mobile .metric-content-text-mobile img{
            width: 28px;
            height: 28px;
        }
        .metric-content-mobile .metric-content-text-mobile .metric-content-export-mb{
            color: #00074D;
            font-size: 14px;
            font-weight: 400;
            line-height:19.6px;
            text-align: left;
        }
        .content-metric-container-in-skill{
            flex-direction: column;
            margin-top: 20px;
        }
        .content-metric-skill-title{
            margin-top: 30px !important;
            font-size: 20px !important;
            font-style: normal !important;
            font-weight: 700 !important;
            line-height: 28px !important;
        }
        

    }
    </style>
   <section class="header-top-metric container-zfe fade-all">
        <div class="header-top-metric-title container-content">
            <h2>Z-extra</h2>
            <b>Trải nghiệm học IELTS tối ưu, toàn diện</b>
            <p>Z-extra là chuỗi các buổi học bổ trợ với nhiều chủ đề và hình thức đa dạng nhằm tạo một môi trường tư duy và học tập hoàn toàn bằng tiếng Anh, giúp học viên phát triển các kỹ năng IELTS một cách dễ dàng, nhanh chóng và toàn diện</p>
        </div>
        <div class="header-top-metric-btn container-zfe fade-all">
            <div class="btn-metric tab-active" onclick="openTab(event, 'Foundation')">
                <div class="btn-metric-active">
                    Foundation
                </div>
            </div>
             <div class="btn-metric btn-non-active" onclick="openTab(event, 'Elementary')">
                <div class="btn-metric-active">
                    Elementary
                </div>
            </div>
             <div class="btn-metric btn-non-active" onclick="openTab(event, 'Intermediate')">
                <div class="btn-metric-active">
                    Intermediate
                </div>
            </div>

        </div>
        <div id="Foundation" class="content-metric container-zfe center tab-active">
            <h4 id="content-metric-h4" class="fade-all">Có phải bạn đang gặp những vấn đề sau?</h4>
            <div class="content-metric-container-in">
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-hello.png" alt="">
                        <p>Thiếu <b>vốn từ </b>thông dụng</p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-ngu-phap.png" alt="">
                        <p>Không có nền tảng <b>ngữ pháp</b></p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-listen-en.png" alt="">
                        <p>Kỹ năng <b>nghe hiểu</b> yếu</p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-kienthuc.png" alt="">
                        <p>Khó tiếp thu <b>kiến thức</b></p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom" id="metric-id-mobile">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-dien-dat-suy-nghi.png" alt="">
                        <p>Hoàn toàn không thể <b>diễn đạt suy nghĩ</b></p>
                    </div>
                </div>
            </div>
            <h4 id="metric-title-in" class="metric-title-in fade-all">Các lớp bổ trợ <b class="metric-title-in-color">Z-extra</b> sẽ giúp bạn</h4>
            <h4 id="metric-title-in-bottom" class="metric-title-in-bottom fade-all">Knowledge</h4>
            <p class="metric-title-in-bottom-text container-content fade-all">
                Các lớp học bổ trợ kiến thức văn hoá và ngôn ngữ - điều kiện nền 
                tảng để phát triển tư duy tiếng Anh. Học viên sẽ được giảng dạy, 
                thực hành giao tiếp và trực tiếp sửa sai bởi giáo viên IELTS 8.0+ bởi giáo viên bản xứ
            </p>
            <!-- <img class="metric-image" src="./assets/images/Knowledge.png" alt="" srcset=""> -->
            <div class="metric-image-container fade-all">
                <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/young-woman-mb.png" alt="">
                <div class="metric-top-content">
                    <div class="metric-top-content-btn-left animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-cmt-mb.png" alt="">
                        <p class="metric-top-content-btn-left-ex">
                            Chủ động thực hành giúp tiếp thu nhanh chóng
                        </p>
                    </div>
                    <div class="metric-top-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-book-mb.png" alt="">
                        <p class="metric-top-content-btn-right-ex">
                            Học ngữ pháp bằng giao tiếp
                        </p>
                    </div>
                </div>
                <div class="metric-center-content">
                    <div class="metric-center-content-btn-left animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-grad-mb.png" alt="">
                        <p class="metric-center-content-btn-left-ex">
                            Bổ sung kiến thức văn hoá ngôn ngữ để luyện tư duy trực tiếp bằng tiếng Anh
                        </p>
                    </div>
                    <div class="metric-center-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-listen-mb.png" alt="">
                        <p class="metric-center-content-btn-right-ex">
                            Luyện phản xạ nghe nói bằng hội thoại thực tế
                        </p>
                    </div>
                </div>
                <div class="metric-bottom-content">
                    <div class="metric-bottom-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-hello-mb.png" alt="">
                        <p class="metric-bottom-content-btn-right-ex">
                            Học từ vựng qua chủ đề gần gũi
                        </p>
                    </div>
                </div>
            </div>
            <div class="metric-content-mobile fade-all">
                <img class="metric-content-mobile-img" src="https://zestforenglish.vn/wp-content/uploads/2023/11/young-woman-mb.png" alt="">
                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-book-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Học ngữ pháp bằng giao tiếp
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-cmt-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Chủ động thực hành giúp tiếp thu nhanh chóng
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-listen-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Luyện phản xạ nghe nói bằng hội thoại thực tế
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-grad-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Bổ sung kiến thức văn hoá ngôn ngữ để luyện tư duy trực tiếp bằng tiếng Anh
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-hello-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Học từ vựng qua chủ đề gần gũi
                    </p>
                </div>
            </div>
            <div class="content-metric-skill">
                <h4 id="content-metric-skill-title" class="content-metric-skill-title fade-all">Skills & Social Hour</h4>
                <div class="content-metric-container-in-skill">
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-open-discussion.png" alt="">
                            <h6>Open discussion</h6>
                            <p>Các buổi thảo luận về những chủ đề xoay quanh cuộc sống hàng ngày. Chủ động rèn luyện Speaking và Listening qua tình huống thực tế</p>
                        </div>
                    </div>
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-Presentation.png" alt="">
                            <h6>Presentation</h6>
                            <p>Luyện tập kỹ năng thuyết trình trước đám đông và khả năng trình bày ý tưởng mạch lạc. Tạo nền tảng tư duy cho Speaking và Writing</p>
                        </div>
                    </div>
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-social-hour.png" alt="">
                            <h6>Social Hour</h6>
                            <p>Hoạt động giải trí kết hợp bổ sung kiến thức tiếng Anh. Đề cao việc phát triển tư duy sáng tạo của học viên, tạo nên phương pháp học không khuôn mẫu</p>
                        </div>
                    </div>
                </div>     
            </div>
        </div>

        <div id="Elementary" class="content-metric container-zfe center">
            <h4 id="content-metric-h4" class="fade-all">Elementary</h4>
            <div class="content-metric-container-in">
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-hello.png" alt="">
                        <p>Thiếu <b>vốn từ </b>thông dụng</p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-ngu-phap.png" alt="">
                        <p>Không có nền tảng <b>ngữ pháp</b></p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-listen-en.png" alt="">
                        <p>Kỹ năng <b>nghe hiểu</b> yếu</p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-kienthuc.png" alt="">
                        <p>Khó tiếp thu <b>kiến thức</b></p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-dien-dat-suy-nghi.png" alt="">
                        <p>Hoàn toàn không thể <b>diễn đạt suy nghĩ</b></p>
                    </div>
                </div>
            </div>
            <h4 id="metric-title-in" class="metric-title-in fade-all">Các lớp bổ trợ <b class="metric-title-in-color">Z-extra</b> sẽ giúp bạn</h4>
            <h4 id="metric-title-in-bottom" class="metric-title-in-bottom fade-all">Elementary</h4>
            <p class="metric-title-in-bottom-text container-content fade-all">Các lớp học bổ trợ kiến thức văn hoá và ngôn ngữ - điều kiện nền tảng để phát triển tư duy tiếng Anh. Học viên sẽ được giảng dạy, thực hành giao tiếp và trực tiếp sửa sai bởi giáo viên IELTS 8.0+ bởi giáo viên bản xứ</p>
            <!-- <img class="metric-image" src="./assets/images/Knowledge.png" alt="" srcset=""> -->
            <div class="metric-image-container fade-all">
                <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/young-woman-mb.png" alt="">
                <div class="metric-top-content">
                    <div class="metric-top-content-btn-left animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-cmt-mb.png" alt="">
                        <p class="metric-top-content-btn-left-ex">
                            Chủ động thực hành giúp tiếp thu nhanh chóng
                        </p>
                    </div>
                    <div class="metric-top-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-book-mb.png" alt="">
                        <p class="metric-top-content-btn-right-ex">
                            Học ngữ pháp bằng giao tiếp
                        </p>
                    </div>
                </div>
                <div class="metric-center-content">
                    <div class="metric-center-content-btn-left animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-grad-mb.png" alt="">
                        <p class="metric-center-content-btn-left-ex">
                            Bổ sung kiến thức văn hoá ngôn ngữ để luyện tư duy trực tiếp bằng tiếng Anh
                        </p>
                    </div>
                    <div class="metric-center-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-listen-mb.png" alt="">
                        <p class="metric-center-content-btn-right-ex">
                            Luyện phản xạ nghe nói bằng hội thoại thực tế
                        </p>
                    </div>
                </div>
                <div class="metric-bottom-content">
                    <div class="metric-bottom-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-hello-mb.png" alt="">
                        <p class="metric-bottom-content-btn-right-ex">
                            Học từ vựng qua chủ đề gần gũi
                        </p>
                    </div>
                </div>
            </div>
           <div class="metric-content-mobile fade-all">
                <img class="metric-content-mobile-img" src="https://zestforenglish.vn/wp-content/uploads/2023/11/young-woman-mb.png" alt="">
                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-book-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Học ngữ pháp bằng giao tiếp
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-cmt-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Chủ động thực hành giúp tiếp thu nhanh chóng
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-listen-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Luyện phản xạ nghe nói bằng hội thoại thực tế
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-grad-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Bổ sung kiến thức văn hoá ngôn ngữ để luyện tư duy trực tiếp bằng tiếng Anh
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-hello-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Học từ vựng qua chủ đề gần gũi
                    </p>
                </div>
            </div>
            <div class="content-metric-skill">
                <h4 id="content-metric-skill-title" class="content-metric-skill-title fade-all">Skills & Social Hour</h4>
                <div class="content-metric-container-in-skill">
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-open-discussion.png" alt="">
                            <h6>Open discussion</h6>
                            <p>Các buổi thảo luận về những chủ đề xoay quanh cuộc sống hàng ngày. Chủ động rèn luyện Speaking và Listening qua tình huống thực tế</p>
                        </div>
                    </div>
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-Presentation.png" alt="">
                            <h6>Presentation</h6>
                            <p>Luyện tập kỹ năng thuyết trình trước đám đông và khả năng trình bày ý tưởng mạch lạc. Tạo nền tảng tư duy cho Speaking và Writing</p>
                        </div>
                    </div>
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-social-hour.png" alt="">
                            <h6>Social Hour</h6>
                            <p>Hoạt động giải trí kết hợp bổ sung kiến thức tiếng Anh. Đề cao việc phát triển tư duy sáng tạo của học viên, tạo nên phương pháp học không khuôn mẫu</p>
                        </div>
                    </div>
                </div>     
            </div>
        </div>

        <div id="Intermediate" class="content-metric container-zfe center">
            <h4 id="content-metric-h4" class="fade-all">Intermediate</h4>
            <div class="content-metric-container-in">
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-hello.png" alt="">
                        <p>Thiếu <b>vốn từ </b>thông dụng</p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-ngu-phap.png" alt="">
                        <p>Không có nền tảng <b>ngữ pháp</b></p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-listen-en.png" alt="">
                        <p>Kỹ năng <b>nghe hiểu</b> yếu</p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-kienthuc.png" alt="">
                        <p>Khó tiếp thu <b>kiến thức</b></p>
                    </div>
                </div>
                <div class="content-metric-export fade-all zoom">
                    <div class="content-metric-export-top">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-dien-dat-suy-nghi.png" alt="">
                        <p>Hoàn toàn không thể <b>diễn đạt suy nghĩ</b></p>
                    </div>
                </div>
            </div>
            <h4 id="metric-title-in" class="metric-title-in fade-all">Các lớp bổ trợ <b class="metric-title-in-color">Z-extra</b> sẽ giúp bạn</h4>
            <h4 id="metric-title-in-bottom" class="metric-title-in-bottom fade-all">Intermediate</h4>
            <p class="metric-title-in-bottom-text container-content fade-all">Các lớp học bổ trợ kiến thức văn hoá và ngôn ngữ - điều kiện nền tảng để phát triển tư duy tiếng Anh. Học viên sẽ được giảng dạy, thực hành giao tiếp và trực tiếp sửa sai bởi giáo viên IELTS 8.0+ bởi giáo viên bản xứ</p>
            <!-- <img class="metric-image" src="./assets/images/Knowledge.png" alt="" srcset=""> -->
            <div class="metric-image-container fade-all">
                <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/young-woman-mb.png" alt="">
                <div class="metric-top-content">
                    <div class="metric-top-content-btn-left animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-cmt-mb.png" alt="">
                        <p class="metric-top-content-btn-left-ex">
                            Chủ động thực hành giúp tiếp thu nhanh chóng
                        </p>
                    </div>
                    <div class="metric-top-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-book-mb.png" alt="">
                        <p class="metric-top-content-btn-right-ex">
                            Học ngữ pháp bằng giao tiếp
                        </p>
                    </div>
                </div>
                <div class="metric-center-content">
                    <div class="metric-center-content-btn-left animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-grad-mb.png" alt="">
                        <p class="metric-center-content-btn-left-ex">
                            Bổ sung kiến thức văn hoá ngôn ngữ để luyện tư duy trực tiếp bằng tiếng Anh
                        </p>
                    </div>
                    <div class="metric-center-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-listen-mb.png" alt="">
                        <p class="metric-center-content-btn-right-ex">
                            Luyện phản xạ nghe nói bằng hội thoại thực tế
                        </p>
                    </div>
                </div>
                <div class="metric-bottom-content">
                    <div class="metric-bottom-content-btn-right animated animatedFadeInUp fadeInUp">
                        <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-hello-mb.png" alt="">
                        <p class="metric-bottom-content-btn-right-ex">
                            Học từ vựng qua chủ đề gần gũi
                        </p>
                    </div>
                </div>
            </div>
            <div class="metric-content-mobile fade-all">
                <img class="metric-content-mobile-img" src="https://zestforenglish.vn/wp-content/uploads/2023/11/young-woman-mb.png" alt="">
                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-book-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Học ngữ pháp bằng giao tiếp
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-cmt-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Chủ động thực hành giúp tiếp thu nhanh chóng
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-listen-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Luyện phản xạ nghe nói bằng hội thoại thực tế
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-grad-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Bổ sung kiến thức văn hoá ngôn ngữ để luyện tư duy trực tiếp bằng tiếng Anh
                    </p>
                </div>

                <div class="metric-content-text-mobile">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-hello-mb.png" alt="">
                    <p class="metric-content-export-mb">
                        Học từ vựng qua chủ đề gần gũi
                    </p>
                </div>
            </div>
            <div class="content-metric-skill">
                <h4 id ="content-metric-skill-title" class="content-metric-skill-title fade-all">Skills & Social Hour</h4>
                <div class="content-metric-container-in-skill">
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-open-discussion.png" alt="">
                            <h6>Open discussion</h6>
                            <p>Các buổi thảo luận về những chủ đề xoay quanh cuộc sống hàng ngày. Chủ động rèn luyện Speaking và Listening qua tình huống thực tế</p>
                        </div>
                    </div>
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-Presentation.png" alt="">
                            <h6>Presentation</h6>
                            <p>Luyện tập kỹ năng thuyết trình trước đám đông và khả năng trình bày ý tưởng mạch lạc. Tạo nền tảng tư duy cho Speaking và Writing</p>
                        </div>
                    </div>
                    <div class="content-metric-export-skill fade-all zoom">
                        <div class="content-metric-export-top-skill">
                            <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-social-hour.png" alt="">
                            <h6>Social Hour</h6>
                            <p>Hoạt động giải trí kết hợp bổ sung kiến thức tiếng Anh. Đề cao việc phát triển tư duy sáng tạo của học viên, tạo nên phương pháp học không khuôn mẫu</p>
                        </div>
                    </div>
                </div>     
            </div>
        </div>
    </section>
<script>
        
        function openTab(evt, metricName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("content-metric");
  
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("btn-metric");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" tab-active", "");
  }
   // Thêm class "btn-non-active" vào tất cả các nút
   var btnMetricCourseActive = document.getElementsByClassName("btn-metric-active");
  for (i = 0; i < btnMetricCourseActive.length; i++) {
    btnMetricCourseActive[i].classList.add("btn-non-active");
  }
  document.getElementById(metricName).style.display = "block";
  evt.currentTarget.className += " tab-active";
}
</script>
    <?php
    return ob_get_clean();
}
add_shortcode('metric','display_metric_zestforenglish');
// end short_code display_metric_zestforenglish




// short_code display_course_zestforenglish
function display_course_zestforenglish(){
    ob_start();
    ?>
    <style>
        *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        
    }
    :root{
    --txt-color-h: #111827;
    --bg-primary: #052FFF;
    --bg-seconday: #ffffff;
    --bg-threeday: #F0F5FF;
    --fw-500: 500;
    --fw-600: 600;
    --fw-700: 700;
    --fw-800: 800;
  
    }
    body{
        background-color: var(--bg-threeday);
        font-family: 'Inter', sans-serif;
    }

.container-zfe{
        max-width: 1140px;
        margin: 0 auto;
    }
    .container-content{
        max-width: 796px;
        margin: 0 auto;
    }
    a{
        text-decoration: none;
    }
/* course */
.tab-active-course{
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        background-color: var(--bg-primary);
        border-radius: 100px;
        font-size: 14px;
        font-weight: var(--fw-600);
        color: var(--bg-seconday);
    }
.course{
        background-color: var(--bg-seconday);
        padding: 48px 0px;
    }
    .course-title h3{
        font-size: 36px;
        font-weight: var(--fw-700);
        color: var(--txt-color-h);
        text-align: center;
    }
    .course-title span{
        font-size: 36px;
        font-weight: var(--fw-700);
        color: var(--bg-primary);
    }
    .header-top-metric-btn-mb{
        gap: 16px;
        margin-top: 24px !important;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        /* overflow-x: scroll; */
    }
    .btn-metric-course-active{
        cursor: pointer;
    }
    .course-content{
        background-color: #F9FAFB;
        border-radius: 24px;
        padding: 32px;
        /* display: flex; */
        align-items: center;
        gap: 20px;
        margin-top: 32px !important;
        display: none;
        flex-direction: row;
        opacity: 0;
        transform: translateX(80px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .course-content.fade-in-right {
      transition-delay: 0.3s;
      opacity: 1;
      transform: translateX(0);
    }

    .course-content.tab-active-course{
        display: flex;
    }
   
    /* Fade in tabs course */
    @-webkit-keyframes  fadeEffectCourse {
    from {opacity: 0;}
    to {opacity: 1;}
    }

    @keyframes  fadeEffectCourse {
    from {opacity: 0;}
    to {opacity: 1;}
    }

    .course-content-left{
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .course-content-left img{
        /* width: 100%; */
        padding: 16px;
        max-width: 486px;
        height: auto;
    }
    .course-content-right{
        display: flex;
        flex-direction: column;
    }
    .course-content-right-title{
        display: flex;
        align-items: self-start;
        gap: 10px;
    }
    .course-content-right-title h3{
        font-size: 24px;
        color: #111827;
        font-weight: var(--fw-700);
        line-height: 32px;
    }
    .course-content-right-title img{
        width: 16px;
        height: 16px;
    }
    .course-content-right-export p{
        color: #111827;
        font-weight: 400;
        font-size: 14px;
        margin-top: 12px;
        line-height: 21px;
    }
    .course-content-right-detail{      
        margin-top: 12px;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .course-content-right-detail img{
        width: 14.051px;
        height: 16.667px;
    }
    .course-content-right-detail p{
        color: #111827;
        font-weight: 400;
        font-size: 14px;
        line-height: 21px;
    }
    .course-content-right-btn{
        margin-top: 20px;
        padding: 12px 8px;
    }
    .course-content-right a{
        color: var(--bg-primary);
        font-weight: var(--fw-600);
        font-size: 16px;
        line-height: 24px;
    }
    .course-content-right a:hover{
        color: #fec200;
    }
/* end course */


/* reponsive for mobile */
@media screen and (max-width: 768px) {
        .container-zfe{
            margin: 0 auto;
            width: 100%;
        }
        .btn{
            width: 100%;
        }

        
        .course{
            margin: 0 auto;
            padding: 48px 16px;
            background-color: var(--bg-seconday);
        }
        .course-title h3{
            font-size: 24px;
        }
        .course-title span{
            font-size: 24px;
        }
        .btn-metric-course{
            /* width: 100%; */
            display: flex;
            align-items: center;
            justify-content: center;
            /* overflow: scroll; */
        }
        .course-content{
            flex-direction: column;
        }
        .course-content-left img{
            width: 100%;
        }
        .header-top-metric-btn-mb{
            justify-content: center;
            align-items: center;
            display: flex;
            margin-top: 32px !important;
            gap: 0px;
            overflow-x: scroll;
        }
        .btn-metric-course-active{
            text-align: center;
            font-size: 14px;
        }
    }
    </style>
   <section class="course">
        <div class="course-title">
            <h3 class="fade-all fade-in-right">Khoá học <span>tại Zest </span></h3>
        </div>
        <div class="header-top-metric-btn-mb container-zfe fade-all">
             <div class="btn-metric-course tab-active-course" onclick="openTabCourse(event, 'zfi')">
                 <div class="btn-metric-course-active">
                     Zest for IELTS
                 </div>
             </div>
             <div class="btn-metric-course btn-non-active" onclick="openTabCourse(event, 'zfb')">
                 <div class="btn-metric-course-active">
                     Zest for Business
                 </div>
             </div>
             <div class="btn-metric-course btn-non-active" onclick="openTabCourse(event, 'zfc')">
                 <div class="btn-metric-course-active">
                     Zest for Communication
                 </div>
             </div>
             <div class="btn-metric-course btn-non-active" onclick="openTabCourse(event, 'zfd')">
                 <div class="btn-metric-course-active">
                     Tự học tại nhà
                 </div>
             </div>
         </div>
         <div id="zfi" class="course-content tab-active-course container-zfe fade-all">
               <div class="course-content-left">
                 <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/couser-image-left.png" alt="">
               </div>
               <div class="course-content-right">
                   <div class="course-content-right-title">
                     <h3>Zest for IELTS</h3>
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bgo.png" alt="">
                   </div>
                   <div class="course-content-right-export">
                     <p>Các khoá học IELTS tại Zest luôn đề cao việc phát triển các kỹ năng IELTS bằng tư duy ngôn ngữ, từ đó cam kết giúp học viên đạt được mục tiêu học tập một cách nhanh chóng và vận dụng tối đa kiến thức đã học vào thực tế.</p>
                   </div>
                   <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Mô hình học không giới hạn Z-extra rút ngắn tối đa lộ trình học</p>
                   </div>
                   <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Giảng dạy bởi 100% giáo viên IELTS 8.0+ và có chứng chỉ giảng dạy TESOL</p>
                   </div>
                   <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Cam kết đầu ra theo lộ trình</p>
                   </div>
                   <div class="course-content-right-btn">
                     <a href="#">Tìm hiểu thêm ></a>
                   </div>
               </div>
         </div>

         <div id="zfb" class="course-content container-zfe fade-all">
             <div class="course-content-left">
               <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/couser-image-left.png" alt="">
             </div>
             <div class="course-content-right">
                 <div class="course-content-right-title">
                   <h3>Zest for Business</h3>
                   <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bgo.png" alt="">
                 </div>
                 <div class="course-content-right-export">
                   <p>Các khoá học IELTS tại Zest luôn đề cao việc phát triển các kỹ năng IELTS bằng tư duy ngôn ngữ, từ đó cam kết giúp học viên đạt được mục tiêu học tập một cách nhanh chóng và vận dụng tối đa kiến thức đã học vào thực tế.</p>
                 </div>
                 <div class="course-content-right-detail">
                   <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                   <p>Mô hình học không giới hạn Z-extra rút ngắn tối đa lộ trình học</p>
                 </div>
                 <div class="course-content-right-detail">
                   <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                   <p>Giảng dạy bởi 100% giáo viên IELTS 8.0+ và có chứng chỉ giảng dạy TESOL</p>
                 </div>
                 <div class="course-content-right-detail">
                   <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                   <p>Cam kết đầu ra theo lộ trình</p>
                 </div>
                 <div class="course-content-right-btn">
                   <a href="#">Tìm hiểu thêm ></a>
                 </div>
             </div>
         </div>

         <div id="zfc" class="course-content container-zfe fade-all">
             <div class="course-content-left">
               <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/couser-image-left.png" alt="">
             </div>
             <div class="course-content-right">
                 <div class="course-content-right-title">
                     <h3>Zest for Communication</h3>
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bgo.png" alt="">
                 </div>
                 <div class="course-content-right-export">
                     <p>Các khoá học IELTS tại Zest luôn đề cao việc phát triển các kỹ năng IELTS bằng tư duy ngôn ngữ, từ đó cam kết giúp học viên đạt được mục tiêu học tập một cách nhanh chóng và vận dụng tối đa kiến thức đã học vào thực tế.</p>
                 </div>
                 <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Mô hình học không giới hạn Z-extra rút ngắn tối đa lộ trình học</p>
                 </div>
                 <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Giảng dạy bởi 100% giáo viên IELTS 8.0+ và có chứng chỉ giảng dạy TESOL</p>
                 </div>
                 <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Cam kết đầu ra theo lộ trình</p>
                 </div>
                 <div class="course-content-right-btn">
                     <a href="#">Tìm hiểu thêm ></a>
                 </div>
             </div>
         </div>

         <div id="zfd" class="course-content container-zfe fade-all">
             <div class="course-content-left">
               <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/couser-image-left.png" alt="">
             </div>
             <div class="course-content-right">
                 <div class="course-content-right-title">
                     <h3>Tự học tại nhà</h3>
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-bgo.png" alt="">
                 </div>
                 <div class="course-content-right-export">
                     <p>Các khoá học IELTS tại Zest luôn đề cao việc phát triển các kỹ năng IELTS bằng tư duy ngôn ngữ, từ đó cam kết giúp học viên đạt được mục tiêu học tập một cách nhanh chóng và vận dụng tối đa kiến thức đã học vào thực tế.</p>
                 </div>
                 <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Mô hình học không giới hạn Z-extra rút ngắn tối đa lộ trình học</p>
                 </div>
                 <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Giảng dạy bởi 100% giáo viên IELTS 8.0+ và có chứng chỉ giảng dạy TESOL</p>
                 </div>
                 <div class="course-content-right-detail">
                     <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-content-export-detail.png" alt="">
                     <p>Cam kết đầu ra theo lộ trình</p>
                 </div>
                 <div class="course-content-right-btn">
                     <a href="#">Tìm hiểu thêm ></a>
                 </div>
             </div>
         </div>
 </section>
    <script>
        
        function openTabCourse(evt, courseName) {
  var i, tabcontentcourse, tablinkscourse;
  tabcontentcourse = document.getElementsByClassName("course-content");
  
  for (i = 0; i < tabcontentcourse.length; i++) {
    tabcontentcourse[i].style.display = "none";
  }
  tablinkscourse = document.getElementsByClassName("btn-metric-course");
  for (i = 0; i < tablinkscourse.length; i++) {
    tablinkscourse[i].className = tablinkscourse[i].className.replace(" tab-active-course", "");
  }
   // Thêm class "btn-non-active" vào tất cả các nút
   var btnMetricCourseActive = document.getElementsByClassName("btn-metric-course-active");
  for (i = 0; i < btnMetricCourseActive.length; i++) {
    btnMetricCourseActive[i].classList.add("btn-non-active");
  }
  document.getElementById(courseName).style.display = "flex";
  evt.currentTarget.className += " tab-active-course";
}
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('course','display_course_zestforenglish');
// end short_code display_course_zestforenglish



// short_code display_teacher_zestforenglish
function display_teacher_zestforenglish(){
    ob_start();
    ?>
    <style>
        *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        
    }
    :root{
    --txt-color-h: #111827;
    --bg-primary: #052FFF;
    --bg-seconday: #ffffff;
    --bg-threeday: #F0F5FF;
    --fw-500: 500;
    --fw-600: 600;
    --fw-700: 700;
    --fw-800: 800;
  
    }
    body{
        background-color: var(--bg-threeday);
        font-family: 'Inter', sans-serif;
    }


.container-zfe{
        max-width: 1140px;
        margin: 0 auto;
    }
    .container-content{
        max-width: 796px;
        margin: 0 auto;
    }
    a{
        text-decoration: none;
    }

/* teacher */
.teacher{
        background-color: var(--bg-threeday);
        width: 100%;
        padding: 48px 0px;
    }
    .teacher .teacher-title h3{
        font-size: 36px;
        font-weight: var(--fw-700);
        line-height: 45px;
        color: var(--txt-color-h);
        text-align: center;
        opacity: 0;
        transform: translateX(-80px);
        transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
    }
    .teacher .teacher-title h3.fade-in-right {
      opacity: 1;
      transform: translateX(0);
      transition-delay: 0.2s;
    }
     .teacher .teacher-title span{
        font-size: 36px;
        font-weight: var(--fw-700);
        line-height: 45px;
        color: var(--bg-primary);
        text-align: center;
    }
    .teacher-row{
        gap: 20px;
        grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
        margin-top: 32px;
        margin-bottom: 32px;
        display: grid;
    }
    .teacher-row-content{
        overflow: hidden;
        background-color: var(--bg-seconday);
        padding: 16px;
        border-radius: 24px;
        opacity: 0;
        transform: translateX(-80px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    .teacher-row-content.fade-in-right {
      opacity: 1;
      transform: translateX(0);
    }
    .teacher-row-content .image-thumbnail-container .image-thumbnail{
        width: 100%;
        height: auto;
        transform-origin: 50% 65%;
        transition: transform 5s, filter 3s ease-in-out;
        filter: brightness(100%);
    }
    .teacher-row-content .image-thumbnail-container .image-thumbnail:hover{
        filter: brightness(100%);
        transform: scale(1.5);
    }
    .teacher-row-content h4{
        margin-top: 8px;
        font-size: 14px;
        font-weight: var(--fw-600);
        line-height: 19.6px;
    }
    .teacher-row-content-detail{
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }
    .teacher-row-content-detail img{
        width: 16px;
        height: 16px;
    }
    .teacher-row-content-detail p{
        font-size: 14px;
        color: #6B7280;
    }
    .teacher-bottom{
       display: flex;
       align-items: center;
       justify-content: center;
    }
    .teacher-bottom .btn-border-teacher{
       line-height: 24px;
       font-size: 16px;
       font-weight: var(--fw-600);
       display: flex;
       align-items: center;
       justify-content: center;
       border-radius:12px;
       padding: 12px 26px;
       border: 1px solid var(--bg-primary);
    }
    .teacher-bottom .btn-border-teacher:hover{
        background: var(--bg-primary);
        cursor: pointer;
        text-decoration: none;
        color: var(--bg-seconday);
    }
/* end teacher */

/* reponsive for mobile */
@media screen and (max-width: 768px) {

        .container-zfe{
            margin: 0 auto;
            width: 100%;
        }
        .btn{
            width: 100%;
        }
        
        
        .teacher{
            padding: 48px 16px;
        }
        .course-content{
            flex-direction: column;
        }
        .course-content-left img{
            width: 100%;
        }
        .teacher .teacher-title h3{
            font-size: 24px;
        }
        .teacher .teacher-title span{
            font-size: 24px;
            display: block;
        }
        .teacher-row{
            margin-top: 24px;
            grid-template-columns: repeat(auto-fit, minmax(161.5px, 1fr));
        }
        .teacher-bottom .btn-border-teacher {
            margin-top: 24px;
        }
        .teacher-row-content-detail p{
            font-size: 12px;
        }
    }

    </style>
   <section class="teacher container-zfe">
        <div class="teacher-title container-zfe">
            <h3 class="fade-all">Đội ngũ <span>Giáo viên chất lượng</span></h3>
        </div>
        <div class="teacher-row container-zfe">
            <div class="teacher-row-content fade-all">
                <div style="overflow: hidden;" class="image-thumbnail-container">
                    <img class="image-thumbnail" src="https://zestforenglish.vn/wp-content/uploads/2023/11/teacher.png" alt="">
                </div>
                <h4>Trần Thành Phong</h4>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-cup.png" alt="">
                    <p>8.5 IELTS Overall</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-bookmark.png" alt="">
                    <p>5+ năm kinh nghiệm</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-student.png" alt="">
                    <p>Chứng chỉ sư phạm TESOL</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-graduationcap.png" alt="">
                    <p>Thạc sĩ BOSTOL, Mỹ</p>
                </div>
            </div>
            <div class="teacher-row-content fade-all">
                <div style="overflow: hidden;" class="image-thumbnail-container">
                    <img class="image-thumbnail" src="https://zestforenglish.vn/wp-content/uploads/2023/11/teacher.png" alt="">
                </div>
                <h4>Trần Thành Phong</h4>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-cup.png" alt="">
                    <p>8.5 IELTS Overall</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-bookmark.png" alt="">
                    <p>5+ năm kinh nghiệm</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-student.png" alt="">
                    <p>Chứng chỉ sư phạm TESOL</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-graduationcap.png" alt="">
                    <p>Thạc sĩ BOSTOL, Mỹ</p>
                </div>
            </div>
            <div class="teacher-row-content fade-all">
                <div style="overflow: hidden;" class="image-thumbnail-container">
                    <img class="image-thumbnail" src="https://zestforenglish.vn/wp-content/uploads/2023/11/teacher.png" alt="">
                </div>
                <h4>Trần Thành Phong</h4>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-cup.png" alt="">
                    <p>8.5 IELTS Overall</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-bookmark.png" alt="">
                    <p>5+ năm kinh nghiệm</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-student.png" alt="">
                    <p>Chứng chỉ sư phạm TESOL</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-graduationcap.png" alt="">
                    <p>Thạc sĩ BOSTOL, Mỹ</p>
                </div>
            </div>

            <div class="teacher-row-content fade-all">
                <div style="overflow: hidden;" class="image-thumbnail-container">
                    <img class="image-thumbnail" src="https://zestforenglish.vn/wp-content/uploads/2023/11/teacher.png" alt="">
                </div>
                <h4>Trần Thành Phong</h4>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-cup.png" alt="">
                    <p>8.5 IELTS Overall</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-bookmark.png" alt="">
                    <p>5+ năm kinh nghiệm</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-student.png" alt="">
                    <p>Chứng chỉ sư phạm TESOL</p>
                </div>
                <div class="teacher-row-content-detail">
                    <img src="https://zestforenglish.vn/wp-content/uploads/2023/11/icon-teacher-graduationcap.png" alt="">
                    <p>Thạc sĩ BOSTOL, Mỹ</p>
                </div>
            </div>
        </div>
        <div class="teacher-bottom">
                <a class="btn-border-teacher" href="#">
                    Xem tất cả
                </a>
            </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('teacher','display_teacher_zestforenglish');
// end short_code display_teacher_zestforenglish