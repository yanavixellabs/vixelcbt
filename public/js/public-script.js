/**
 * VixelCBT Public JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        VixelCBT.init();
    });
    
    // Main VixelCBT object
    window.VixelCBT = {
        
        init: function() {
            this.initFormValidation();
            this.initDashboardTabs();
            this.initExamInterface();
            this.initGraduationCheck();
            this.initLoadingStates();
        },
        
        // Form validation
        initFormValidation: function() {
            // Registration form validation
            $('#vixelcbt-register').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    VixelCBT.showError('Password confirmation does not match.');
                    return false;
                }
                
                const nisn = $('#nisn').val();
                if (nisn && nisn.length !== 10) {
                    e.preventDefault();
                    VixelCBT.showError('NISN must be exactly 10 digits.');
                    return false;
                }
            });
            
            // Real-time password validation
            $('#confirm_password').on('input', function() {
                const password = $('#password').val();
                const confirmPassword = $(this).val();
                
                if (confirmPassword && password !== confirmPassword) {
                    $(this).addClass('error');
                    $(this).siblings('.error-message').remove();
                    $(this).after('<span class="error-message">Passwords do not match</span>');
                } else {
                    $(this).removeClass('error');
                    $(this).siblings('.error-message').remove();
                }
            });
        },
        
        // Dashboard tabs functionality
        initDashboardTabs: function() {
            $('.tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                
                // Remove active classes
                $('.tab-btn').removeClass('active');
                $('.tab-content').removeClass('active');
                
                // Add active classes
                $(this).addClass('active');
                $('#tab-' + tabId).addClass('active');
                
                // Store active tab in localStorage
                localStorage.setItem('vixelcbt_active_tab', tabId);
            });
            
            // Restore active tab from localStorage
            const activeTab = localStorage.getItem('vixelcbt_active_tab');
            if (activeTab) {
                $('.tab-btn[data-tab="' + activeTab + '"]').click();
            }
        },
        
        // Exam interface functionality
        initExamInterface: function() {
            const examInterface = $('.vixelcbt-exam-interface');
            if (examInterface.length === 0) return;
            
            this.examState = {
                currentQuestion: 1,
                totalQuestions: 0,
                answers: {},
                timeRemaining: 0,
                isFullscreen: false
            };
            
            // Initialize exam flow
            this.loadExamData();
            
            // Token verification
            $('#verify-token').on('click', function() {
                VixelCBT.verifyToken();
            });
            
            // Start exam
            $('#start-exam').on('click', function() {
                VixelCBT.startExam();
            });
            
            // Navigation
            $('#prev-question').on('click', function() {
                VixelCBT.navigateQuestion(-1);
            });
            
            $('#next-question').on('click', function() {
                VixelCBT.navigateQuestion(1);
            });
            
            // Save answers
            $('#save-answers').on('click', function() {
                VixelCBT.saveCurrentAnswer();
            });
            
            // Submit exam
            $('#submit-exam').on('click', function() {
                VixelCBT.confirmSubmitExam();
            });
            
            // Auto-save functionality
            $(document).on('change', '.question-input', function() {
                clearTimeout(VixelCBT.autoSaveTimer);
                VixelCBT.autoSaveTimer = setTimeout(function() {
                    VixelCBT.saveCurrentAnswer();
                }, 2000);
            });
            
            // Blur detection for anti-cheat
            this.initBlurDetection();
            
            // Prevent right-click and copy-paste
            this.initAntiCheat();
        },
        
        // Load exam data
        loadExamData: function() {
            const examInterface = $('.vixelcbt-exam-interface');
            const modulId = examInterface.data('modul');
            const sesiId = examInterface.data('sesi');
            const tokenEnabled = examInterface.data('token') === 'on';
            
            if (!modulId || !sesiId) {
                this.showError('Invalid exam configuration.');
                return;
            }
            
            // Show appropriate screen based on configuration
            if (tokenEnabled) {
                this.showScreen('exam-token');
            } else {
                this.showScreen('exam-instructions');
            }
        },
        
        // Verify exam token
        verifyToken: function() {
            const token = $('#exam-token-input').val().trim().toUpperCase();
            const examInterface = $('.vixelcbt-exam-interface');
            const sesiId = examInterface.data('sesi');
            
            if (!token) {
                this.showError('Please enter a token.');
                return;
            }
            
            // Disable button during verification
            $('#verify-token').prop('disabled', true).text('Verifying...');
            
            $.ajax({
                url: vixelcbt_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'vixelcbt_verify_token',
                    token: token,
                    sesi_id: sesiId,
                    nonce: vixelcbt_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VixelCBT.showScreen('exam-instructions');
                    } else {
                        VixelCBT.showTokenError(response.data.message);
                    }
                },
                error: function() {
                    VixelCBT.showTokenError('Connection error. Please try again.');
                },
                complete: function() {
                    $('#verify-token').prop('disabled', false).text('Verify Token');
                }
            });
        },
        
        // Show token error
        showTokenError: function(message) {
            const errorDiv = $('#token-error');
            errorDiv.text(message).show();
            
            // Clear error after 5 seconds
            setTimeout(function() {
                errorDiv.fadeOut();
            }, 5000);
        },
        
        // Start exam
        startExam: function() {
            const examInterface = $('.vixelcbt-exam-interface');
            const fullscreenEnabled = examInterface.data('fullscreen') === 'on';
            
            // Request fullscreen if enabled
            if (fullscreenEnabled) {
                this.requestFullscreen();
            }
            
            // Load exam questions
            this.loadExamQuestions();
        },
        
        // Request fullscreen mode
        requestFullscreen: function() {
            const element = document.documentElement;
            
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
            
            this.examState.isFullscreen = true;
            
            // Monitor fullscreen changes
            $(document).on('fullscreenchange mozfullscreenchange webkitfullscreenchange msfullscreenchange', function() {
                if (!document.fullscreenElement && VixelCBT.examState.isFullscreen) {
                    VixelCBT.handleFullscreenViolation();
                }
            });
        },
        
        // Handle fullscreen violation
        handleFullscreenViolation: function() {
            this.logViolation('fullscreen_exit');
            
            if (confirm('You have exited fullscreen mode. The exam must be in fullscreen. Click OK to continue in fullscreen mode.')) {
                this.requestFullscreen();
            } else {
                this.pauseExam();
            }
        },
        
        // Load exam questions
        loadExamQuestions: function() {
            const examInterface = $('.vixelcbt-exam-interface');
            const modulId = examInterface.data('modul');
            const sesiId = examInterface.data('sesi');
            
            $.ajax({
                url: vixelcbt_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'vixelcbt_exam_start',
                    modul_id: modulId,
                    sesi_id: sesiId,
                    nonce: vixelcbt_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VixelCBT.examState.questions = response.data.questions;
                        VixelCBT.examState.attemptId = response.data.attempt_id;
                        VixelCBT.examState.totalQuestions = response.data.questions.length;
                        VixelCBT.examState.timeRemaining = response.data.remaining_time;
                        
                        VixelCBT.showScreen('exam-interface');
                        VixelCBT.renderQuestion(1);
                        VixelCBT.startTimer();
                        VixelCBT.renderQuestionNumbers();
                    } else {
                        VixelCBT.showError(response.data.message);
                    }
                },
                error: function() {
                    VixelCBT.showError('Failed to load exam. Please try again.');
                }
            });
        },
        
        // Render question
        renderQuestion: function(questionNumber) {
            const question = this.examState.questions[questionNumber - 1];
            if (!question) return;
            
            this.examState.currentQuestion = questionNumber;
            
            // Update question counter
            $('#question-counter').text(`Question ${questionNumber} of ${this.examState.totalQuestions}`);
            
            // Render question content
            let questionHtml = `
                <div class="question-header">
                    <h3>Question ${questionNumber}</h3>
                </div>
                <div class="question-text">
                    ${question.pertanyaan}
                </div>
            `;
            
            // Add media if present
            if (question.media_url) {
                if (question.tipe === 'audio') {
                    questionHtml += `<audio controls class="question-media">
                        <source src="${question.media_url}" type="audio/mpeg">
                        Your browser does not support audio playback.
                    </audio>`;
                } else if (question.tipe === 'video') {
                    questionHtml += `<video controls class="question-media">
                        <source src="${question.media_url}" type="video/mp4">
                        Your browser does not support video playback.
                    </video>`;
                }
            }
            
            // Render answer options based on question type
            questionHtml += this.renderAnswerOptions(question);
            
            $('#question-content').html(questionHtml);
            
            // Load saved answer if exists
            this.loadSavedAnswer(question.soal_id);
            
            // Update navigation buttons
            this.updateNavigationButtons();
        },
        
        // Render answer options
        renderAnswerOptions: function(question) {
            let optionsHtml = '<div class="answer-options">';
            
            switch (question.tipe) {
                case 'pg':
                    const options = JSON.parse(question.opsi || '[]');
                    options.forEach((option, index) => {
                        const optionId = String.fromCharCode(65 + index); // A, B, C, D
                        optionsHtml += `
                            <label class="option-label">
                                <input type="radio" name="answer_${question.soal_id}" value="${optionId}" class="question-input">
                                <span class="option-text">${optionId}. ${option}</span>
                            </label>
                        `;
                    });
                    break;
                    
                case 'checkbox':
                    const checkboxOptions = JSON.parse(question.opsi || '[]');
                    checkboxOptions.forEach((option, index) => {
                        const optionId = String.fromCharCode(65 + index);
                        optionsHtml += `
                            <label class="option-label">
                                <input type="checkbox" name="answer_${question.soal_id}[]" value="${optionId}" class="question-input">
                                <span class="option-text">${optionId}. ${option}</span>
                            </label>
                        `;
                    });
                    break;
                    
                case 'esai':
                    optionsHtml += `
                        <textarea name="answer_${question.soal_id}" class="question-input essay-input" rows="8" 
                                  placeholder="Type your answer here..."></textarea>
                    `;
                    break;
                    
                case 'upload':
                    optionsHtml += `
                        <input type="file" name="answer_${question.soal_id}" class="question-input file-input" 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                        <div class="upload-info">
                            <p>Allowed file types: JPG, PNG, PDF, DOC, DOCX</p>
                            <p>Maximum file size: 5MB</p>
                        </div>
                    `;
                    break;
            }
            
            optionsHtml += '</div>';
            return optionsHtml;
        },
        
        // Load saved answer
        loadSavedAnswer: function(soalId) {
            const savedAnswer = this.examState.answers[soalId];
            if (!savedAnswer) return;
            
            const inputs = $(`input[name="answer_${soalId}"], input[name="answer_${soalId}[]"], textarea[name="answer_${soalId}"]`);
            
            if (inputs.attr('type') === 'radio') {
                inputs.filter(`[value="${savedAnswer}"]`).prop('checked', true);
            } else if (inputs.attr('type') === 'checkbox') {
                const values = Array.isArray(savedAnswer) ? savedAnswer : [savedAnswer];
                values.forEach(value => {
                    inputs.filter(`[value="${value}"]`).prop('checked', true);
                });
            } else if (inputs.is('textarea')) {
                inputs.val(savedAnswer);
            }
        },
        
        // Save current answer
        saveCurrentAnswer: function() {
            const currentQuestion = this.examState.questions[this.examState.currentQuestion - 1];
            if (!currentQuestion) return;
            
            const soalId = currentQuestion.soal_id;
            let answer = null;
            
            // Get answer based on question type
            const inputs = $(`input[name="answer_${soalId}"], input[name="answer_${soalId}[]"], textarea[name="answer_${soalId}"]`);
            
            if (inputs.attr('type') === 'radio') {
                answer = inputs.filter(':checked').val();
            } else if (inputs.attr('type') === 'checkbox') {
                answer = [];
                inputs.filter(':checked').each(function() {
                    answer.push($(this).val());
                });
            } else if (inputs.is('textarea')) {
                answer = inputs.val();
            } else if (inputs.attr('type') === 'file') {
                // File upload handled separately
                return;
            }
            
            // Save to local state
            this.examState.answers[soalId] = answer;
            
            // Save to server
            $.ajax({
                url: vixelcbt_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'vixelcbt_exam_save_answer',
                    attempt_id: this.examState.attemptId,
                    soal_id: soalId,
                    jawaban: answer,
                    nonce: vixelcbt_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VixelCBT.showSaveIndicator();
                        VixelCBT.updateQuestionNumbers();
                    }
                }
            });
        },
        
        // Show save indicator
        showSaveIndicator: function() {
            // Show a subtle indicator that answers are saved
            const indicator = $('<div class="save-indicator">Saved</div>');
            $('body').append(indicator);
            
            setTimeout(function() {
                indicator.fadeOut(function() {
                    $(this).remove();
                });
            }, 2000);
        },
        
        // Navigate questions
        navigateQuestion: function(direction) {
            const newQuestion = this.examState.currentQuestion + direction;
            
            if (newQuestion >= 1 && newQuestion <= this.examState.totalQuestions) {
                this.saveCurrentAnswer(); // Save before navigating
                this.renderQuestion(newQuestion);
            }
        },
        
        // Update navigation buttons
        updateNavigationButtons: function() {
            $('#prev-question').prop('disabled', this.examState.currentQuestion === 1);
            $('#next-question').prop('disabled', this.examState.currentQuestion === this.examState.totalQuestions);
        },
        
        // Render question numbers navigation
        renderQuestionNumbers: function() {
            let numbersHtml = '';
            
            for (let i = 1; i <= this.examState.totalQuestions; i++) {
                const isAnswered = Object.keys(this.examState.answers).includes(String(this.examState.questions[i - 1].soal_id));
                const isCurrent = i === this.examState.currentQuestion;
                
                let classes = 'question-number';
                if (isAnswered) classes += ' answered';
                if (isCurrent) classes += ' current';
                
                numbersHtml += `<button class="${classes}" data-question="${i}">${i}</button>`;
            }
            
            $('#question-numbers').html(numbersHtml);
            
            // Add click handlers
            $('.question-number').on('click', function() {
                const questionNumber = parseInt($(this).data('question'));
                VixelCBT.saveCurrentAnswer();
                VixelCBT.renderQuestion(questionNumber);
                VixelCBT.updateQuestionNumbers();
            });
        },
        
        // Update question numbers
        updateQuestionNumbers: function() {
            $('.question-number').each(function() {
                const questionNumber = parseInt($(this).data('question'));
                const question = VixelCBT.examState.questions[questionNumber - 1];
                const isAnswered = Object.keys(VixelCBT.examState.answers).includes(String(question.soal_id));
                const isCurrent = questionNumber === VixelCBT.examState.currentQuestion;
                
                $(this).removeClass('answered current');
                if (isAnswered) $(this).addClass('answered');
                if (isCurrent) $(this).addClass('current');
            });
        },
        
        // Start exam timer
        startTimer: function() {
            if (this.examState.timeRemaining <= 0) return;
            
            this.timerInterval = setInterval(function() {
                VixelCBT.examState.timeRemaining--;
                VixelCBT.updateTimerDisplay();
                
                // Auto-submit when time runs out
                if (VixelCBT.examState.timeRemaining <= 0) {
                    clearInterval(VixelCBT.timerInterval);
                    VixelCBT.autoSubmitExam();
                }
                
                // Save progress periodically
                if (VixelCBT.examState.timeRemaining % 60 === 0) {
                    VixelCBT.saveCurrentAnswer();
                }
            }, 1000);
        },
        
        // Update timer display
        updateTimerDisplay: function() {
            const hours = Math.floor(this.examState.timeRemaining / 3600);
            const minutes = Math.floor((this.examState.timeRemaining % 3600) / 60);
            const seconds = this.examState.timeRemaining % 60;
            
            const timeString = [hours, minutes, seconds]
                .map(t => t.toString().padStart(2, '0'))
                .join(':');
            
            $('#time-remaining').text(timeString);
            
            // Change color when time is running low
            if (this.examState.timeRemaining <= 300) { // 5 minutes
                $('#time-remaining').addClass('time-critical');
            } else if (this.examState.timeRemaining <= 900) { // 15 minutes
                $('#time-remaining').addClass('time-warning');
            }
        },
        
        // Confirm exam submission
        confirmSubmitExam: function() {
            const unansweredCount = this.examState.totalQuestions - Object.keys(this.examState.answers).length;
            
            let message = 'Are you sure you want to submit your exam?';
            if (unansweredCount > 0) {
                message += `\n\nYou have ${unansweredCount} unanswered questions.`;
            }
            
            if (confirm(message)) {
                this.submitExam();
            }
        },
        
        // Submit exam
        submitExam: function() {
            // Save current answer first
            this.saveCurrentAnswer();
            
            // Clear timer
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
            
            // Disable all inputs
            $('.question-input').prop('disabled', true);
            $('#submit-exam').prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: vixelcbt_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'vixelcbt_exam_submit',
                    attempt_id: this.examState.attemptId,
                    nonce: vixelcbt_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VixelCBT.showScreen('exam-completed');
                        VixelCBT.exitFullscreen();
                    } else {
                        VixelCBT.showError(response.data.message);
                        $('.question-input').prop('disabled', false);
                        $('#submit-exam').prop('disabled', false).text('Submit Exam');
                    }
                },
                error: function() {
                    VixelCBT.showError('Failed to submit exam. Please try again.');
                    $('.question-input').prop('disabled', false);
                    $('#submit-exam').prop('disabled', false).text('Submit Exam');
                }
            });
        },
        
        // Auto-submit exam when time runs out
        autoSubmitExam: function() {
            this.saveCurrentAnswer();
            
            $.ajax({
                url: vixelcbt_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'vixelcbt_exam_submit',
                    attempt_id: this.examState.attemptId,
                    auto_submit: true,
                    nonce: vixelcbt_public.nonce
                },
                success: function(response) {
                    VixelCBT.showScreen('exam-completed');
                    VixelCBT.exitFullscreen();
                },
                error: function() {
                    VixelCBT.showError('Time expired. Exam auto-submitted with saved answers.');
                    setTimeout(function() {
                        window.location.href = '/dashboard';
                    }, 3000);
                }
            });
        },
        
        // Exit fullscreen
        exitFullscreen: function() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            
            this.examState.isFullscreen = false;
        },
        
        // Initialize blur detection
        initBlurDetection: function() {
            let blurCount = 0;
            const maxBlurs = 3; // Configurable
            
            $(window).on('blur', function() {
                if (VixelCBT.examState.isFullscreen) {
                    blurCount++;
                    VixelCBT.logViolation('window_blur', { count: blurCount });
                    
                    if (blurCount >= maxBlurs) {
                        alert('Multiple window focus violations detected. Your exam may be flagged for review.');
                    } else {
                        const remainingBlurs = maxBlurs - blurCount;
                        alert(`Warning: You switched away from the exam window. ${remainingBlurs} more violations will result in exam review.`);
                    }
                }
            });
        },
        
        // Initialize anti-cheat measures
        initAntiCheat: function() {
            // Disable right-click
            $(document).on('contextmenu', function(e) {
                if (VixelCBT.examState.isFullscreen) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Disable copy-paste shortcuts
            $(document).on('keydown', function(e) {
                if (VixelCBT.examState.isFullscreen) {
                    // Disable Ctrl+C, Ctrl+V, Ctrl+A, Ctrl+S, F12, etc.
                    if ((e.ctrlKey && (e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 65 || e.keyCode === 83)) || 
                        e.keyCode === 123) {
                        e.preventDefault();
                        VixelCBT.logViolation('keyboard_shortcut', { keyCode: e.keyCode });
                        return false;
                    }
                }
            });
            
            // Disable text selection
            $(document).on('selectstart', function(e) {
                if (VixelCBT.examState.isFullscreen) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        // Log violation
        logViolation: function(type, data) {
            $.ajax({
                url: vixelcbt_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'vixelcbt_log_violation',
                    attempt_id: this.examState.attemptId,
                    violation_type: type,
                    violation_data: JSON.stringify(data || {}),
                    nonce: vixelcbt_public.nonce
                }
            });
        },
        
        // Pause exam
        pauseExam: function() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
            
            alert('Exam paused due to violation. Please contact the proctor.');
        },
        
        // Show exam screen
        showScreen: function(screenId) {
            $('.exam-screen').hide();
            $('#' + screenId).show();
        },
        
        // Graduation check functionality
        initGraduationCheck: function() {
            // Rate limiting for graduation check
            let searchCount = 0;
            const maxSearches = 5;
            
            $('#kelulusan-form').on('submit', function(e) {
                e.preventDefault();
                
                if (searchCount >= maxSearches) {
                    VixelCBT.showError('Too many searches. Please try again later.');
                    return;
                }
                
                searchCount++;
                
                const formData = $(this).serialize();
                $('#kelulusan-result').html('<div class="loading">Searching...</div>').show();
                
                $.ajax({
                    url: vixelcbt_public.ajax_url,
                    type: 'POST',
                    data: formData + '&action=vixelcbt_check_graduation',
                    success: function(response) {
                        if (response.success) {
                            $('#kelulusan-result').html(response.data.html);
                        } else {
                            $('#kelulusan-result').html('<div class="error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#kelulusan-result').html('<div class="error">System error occurred.</div>');
                    }
                });
            });
        },
        
        // Initialize loading states
        initLoadingStates: function() {
            // Show loading spinner for AJAX requests
            $(document).ajaxStart(function() {
                $('body').addClass('vixelcbt-loading');
            }).ajaxStop(function() {
                $('body').removeClass('vixelcbt-loading');
            });
        },
        
        // Utility functions
        showError: function(message) {
            alert(message); // Could be replaced with a more elegant notification system
        },
        
        showSuccess: function(message) {
            alert(message); // Could be replaced with a more elegant notification system
        }
    };
    
})(jQuery);