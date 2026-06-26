        <div class="card">
            <h3>Department-Specific Performance & Risk Analytics</h3>
            <p class="muted">This feature shows risk interpretaion. And flag them as low and hig.h</p>
            <table>
                <tr>
                    <th>Department</th>
                    <th>Completed Courses</th>
                    <th>Average CGPA</th>
                    <th>Grades Below 2.00</th>
                    <th>Risk Interpretation</th>
                </tr>
                <?php if (empty($dept_stats)): ?>
                    <tr><td colspan="5">Course is not completed yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($dept_stats as $dept):
                        $avg = (float)$dept['avg_gpa'];
                        if ($avg < 2.00) { $riskClass = 'risk-high'; $riskText = 'High Risk'; }
                        elseif ($avg < 2.70) { $riskClass = 'risk-medium'; $riskText = 'Medium Risk'; }
                        else { $riskClass = 'risk-low'; $riskText = 'Low Risk'; }
                    ?>
                        <tr>
                            <td><strong><?php echo e($dept['dept_name']); ?></strong></td>
                            <td><?php echo e($dept['completed_count']); ?></td>
                            <td><?php echo e(number_format($avg, 2)); ?></td>
                            <td><?php echo e($dept['weak_count']); ?></td>
                            <td><span class="badge <?php echo $riskClass; ?>"><?php echo $riskText; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>

