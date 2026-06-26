        <div class="card">
            <h3>Intelligent Course Retake Management</h3>
            <p class="muted">Select any course you want to retake for grade improvement. This feature verifies that you completed it before and schedules the retake first.</p>

            <form method="POST" action="recommendation.php">
                <?php if (empty($retake_eligible)): ?>
                    <p>No retake-priority courses found. Courses with GPA below <?php echo e(number_format($retake_threshold, 2)); ?> will appear here.</p>
                <?php else: ?>
                    <div class="retake-list">
                        <?php foreach ($retake_eligible as $course_id => $course): ?>
                            <div class="retake-item">
                                <label>
                                    <input type="checkbox" name="retake_courses[]" value="<?php echo e($course_id); ?>" <?php echo isset($selected_retake_ids[$course_id]) ? 'checked' : ''; ?>>
                                    <strong><?php echo e($course['course_code']); ?></strong> — <?php echo e($course['course_name']); ?>
                                </label>
                                <div class="note">Previous GPA: <?php echo e($course['gpa']); ?> | Credits: <?php echo e($course['credit_hours']); ?> | Department: <?php echo e($course['dept_name']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <br>
                <button type="submit" name="generate_plan" class="btn btn-green">Generate Updated Plan</button>
            </form>
        </div>

