<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <title>Mahen Lani’s Space</title>
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link href="/css/todo.css?v={{ time() }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <div class="container">
        <h2 class="page-title">Mahen Lani’s To-Do List</h2>

        <!-- Tanggal Aktif -->
        <div class="date-display">
            {{ $formattedDate }}
        </div>

        <!-- Search, Date Picker & Toggle Semua -->
        <div class="search-date-wrapper">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
            </div>
            <div class="controls-right">
                <input type="text" id="datePicker" class="date-input" value="{{ $selectedDate }}">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="showAllTasks">
                    <label class="form-check-label" for="showAllTasks">All Tasks</label>
                </div>
            </div>
        </div>

        <!-- 🌟 Floating Action Button -->
        <div class="fab-container">
            <button id="openTaskForm" class="fab">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <!-- 📥 Form Tambah Task -->
        <div id="taskFormContainer" class="task-form-slide">
            <form id="taskForm" class="inline-form">
                <input type="text" id="taskInput" placeholder="What do you plan to work on today? ..." required>
                <input type="date" id="taskDate" value="{{ $selectedDate }}" required>
                <button type="submit" class="btn-add">Add</button>
                <button type="button" id="cancelForm" class="btn-cancel">×</button>
            </form>
        </div>

        <!-- Daftar Task -->
        <ul id="taskList" class="list-unstyled">
            @foreach ($tasks as $task)
                <li class="task-item {{ $task->completed ? 'completed' : '' }}" data-id="{{ $task->id }}">
                    <div class="task-content">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                @if ($task->created_time && $task->created_date)
                                    <div class="task-time whatsapp-style mb-1">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        {{ \Carbon\Carbon::parse($task->created_date)->isToday()
                                            ? 'Today'
                                            : (\Carbon\Carbon::parse($task->created_date)->isYesterday()
                                                ? 'Yesterday'
                                                : \Carbon\Carbon::parse($task->created_date)->format('M j')) }},
                                        {{ \Carbon\Carbon::parse($task->created_time)->format('H:i') }}
                                    </div>
                                @endif
                                <div class="task-title">{{ $task->title }}</div>
                                @if ($task->description)
                                    <div class="task-desc">{{ $task->description }}</div>
                                @endif
                            </div>
                            <div class="task-actions d-flex gap-2">
                                <button class="action-btn edit-btn" data-id="{{ $task->id }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn complete-btn {{ $task->completed ? 'completed' : '' }}"
                                    data-id="{{ $task->id }}" data-completed="{{ $task->completed ? 1 : 0 }}"
                                    title="{{ $task->completed ? 'Undo' : 'Done' }}">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button class="action-btn delete-btn" data-id="{{ $task->id }}" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="edit-form mt-3" style="display:none;">
                        <div class="mb-2">
                            <input type="text" class="form-control edit-title" value="{{ $task->title }}" required>
                        </div>
                        <div class="mb-2">
                            <textarea class="form-control edit-desc" rows="2">{{ $task->description }}</textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-success save-edit"
                                data-id="{{ $task->id }}">Save</button>
                            <button class="btn btn-sm btn-secondary cancel-edit">Cancel</button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <!-- Navigasi Tanggal -->
        <div class="date-nav mt-4">
            <a href="/{{ \Carbon\Carbon::parse($selectedDate)->subDay()->toDateString() }}" class="btn">
                <i class="fa-solid fa-chevron-left"></i> </a>
            <a href="/{{ now()->toDateString() }}" class="btn btn-outline-primary">Today</a>
            <a href="/{{ \Carbon\Carbon::parse($selectedDate)->addDay()->toDateString() }}" class="btn">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // ✅ Render task dengan opsi tampilkan tanggal
        function renderTaskList(tasks, showDate = false) {
            const taskList = $('#taskList');
            taskList.empty();

            if (tasks.length === 0) {
                taskList.html('<li class="text-center py-3 text-muted">No tasks found</li>');
                return;
            }

            if (showDate) {
                // Kelompokkan berdasarkan tanggal
                const grouped = {};
                tasks.forEach(task => {
                    if (!grouped[task.date]) grouped[task.date] = [];
                    grouped[task.date].push(task);
                });

                // Urutkan dari terbaru
                Object.keys(grouped)
                    .sort((a, b) => new Date(b) - new Date(a))
                    .forEach(dateKey => {
                        const formatted = new Date(dateKey).toLocaleDateString('en-US', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                        taskList.append(`<li class="task-date-header">${formatted}</li>`);
                        grouped[dateKey].forEach(task => {
                            appendTaskItem(task);
                        });
                    });
            } else {
                tasks.forEach(task => appendTaskItem(task));
            }
        }

        // ✅ Helper: buat elemen task dengan timestamp WhatsApp-style
        function appendTaskItem(task) {
            const descHtml = task.description ? `<div class="task-desc">${task.description}</div>` : '';
            const completedClass = task.completed ? 'completed' : '';
            const completedBtnClass = task.completed ? 'completed' : '';
            const completedBtnTitle = task.completed ? 'Undo' : 'Done';
            const completedBtnData = task.completed ? '1' : '0';

            // Format waktu seperti WhatsApp berdasarkan tanggal pembuatan
            let timeHtml = '';
            if (task.created_time && task.created_date) {
                const createdDate = new Date(task.created_date);
                const today = new Date();
                const yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);

                let dayText = '';
                if (createdDate.toDateString() === today.toDateString()) {
                    dayText = 'Today';
                } else if (createdDate.toDateString() === yesterday.toDateString()) {
                    dayText = 'Yesterday';
                } else {
                    dayText = createdDate.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric'
                    });
                }

                // Format waktu ke H:i (tanpa detik)
                const timeParts = task.created_time.split(':');
                const formattedTime = timeParts[0] + ':' + timeParts[1];

                timeHtml = `
            <div class="task-time whatsapp-style mb-1">
                <i class="fa-regular fa-clock me-1"></i>
                ${dayText}, ${formattedTime}
            </div>
        `;
            }

            $('#taskList').append(`
        <li class="task-item ${completedClass}" data-id="${task.id}">
            <div class="task-content">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        ${timeHtml}
                        <div class="task-title">${task.title}</div>
                        ${descHtml}
                    </div>
                    <div class="task-actions d-flex gap-2">
                        <button class="action-btn edit-btn" data-id="${task.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn complete-btn ${completedBtnClass}"
                            data-id="${task.id}" data-completed="${completedBtnData}"
                            title="${completedBtnTitle}">
                            <i class="fas fa-check-circle"></i>
                        </button>
                        <button class="action-btn delete-btn" data-id="${task.id}" title="Hapus">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="edit-form mt-3" style="display:none;">
                <div class="mb-2">
                    <input type="text" class="form-control edit-title" value="${task.title}" required>
                </div>
                <div class="mb-2">
                    <textarea class="form-control edit-desc" rows="2">${task.description || ''}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success save-edit" data-id="${task.id}">Save</button>
                    <button class="btn btn-sm btn-secondary cancel-edit">Cancel</button>
                </div>
            </div>
        </li>
    `);
        }

        function loadAllTasks() {
            $.get('/tasks/all', function(tasks) {
                $('.date-display').text('All Tasks');
                renderTaskList(tasks, true);
            }).fail(function() {
                alert('We couldn’t load your tasks. Please try again.');
            });
        }

        function loadTasksByDate(date) {
            $.get('/tasks/by-date', {
                date: date
            }, function(response) {
                $('.date-display').text(response.formatted_date);
                renderTaskList(response.tasks, false);
            }).fail(function() {
                alert('We couldn’t load tasks for this date. Please try again.');
            });
        }

        // ✅ Event handler
        $('#showAllTasks').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('#datePicker').prop('disabled', isChecked);
            if (isChecked) {
                loadAllTasks();
            } else {
                const date = $('#datePicker').val();
                if (date) loadTasksByDate(date);
            }
        });

        $('#datePicker').on('change', function() {
            if (!$('#showAllTasks').is(':checked')) {
                const date = $(this).val();
                if (date) loadTasksByDate(date);
            }
        });

        $('#taskForm').on('submit', function(e) {
            e.preventDefault();
            let title = $('#taskInput').val().trim();
            let date = $('#taskDate').val();
            if (!title || !date) return;

            $.post('/tasks', {
                title: title,
                description: null,
                date: date
            }, function(task) {
                const descHtml = task.description ? '<div class="task-desc">' + task.description +
                    '</div>' : '';

                // Format waktu untuk task baru berdasarkan tanggal pembuatan
                let timeHtml = '';
                if (task.created_time && task.created_date) {
                    const createdDate = new Date(task.created_date);
                    const today = new Date();
                    const yesterday = new Date();
                    yesterday.setDate(yesterday.getDate() - 1);

                    let dayText = '';
                    if (createdDate.toDateString() === today.toDateString()) {
                        dayText = 'Today';
                    } else if (createdDate.toDateString() === yesterday.toDateString()) {
                        dayText = 'Yesterday';
                    } else {
                        dayText = createdDate.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric'
                        });
                    }

                    // Format waktu ke H:i (tanpa detik)
                    const timeParts = task.created_time.split(':');
                    const formattedTime = timeParts[0] + ':' + timeParts[1];

                    timeHtml = `
                <div class="task-time whatsapp-style mb-1">
                    <i class="fa-regular fa-clock me-1"></i>
                    ${dayText}, ${formattedTime}
                </div>
            `;
                }

                $('#taskList').prepend(`
            <li class="task-item" data-id="${task.id}">
                <div class="task-content">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            ${timeHtml}
                            <div class="task-title">${task.title}</div>
                            ${descHtml}
                        </div>
                        <div class="task-actions d-flex gap-2">
                            <button class="action-btn edit-btn" data-id="${task.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn complete-btn" data-id="${task.id}" data-completed="0" title="Mark as Done">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <button class="action-btn delete-btn" data-id="${task.id}" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="edit-form mt-3" style="display:none;">
                    <div class="mb-2">
                        <input type="text" class="form-control edit-title" value="${task.title}" required>
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control edit-desc" rows="2"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success save-edit" data-id="${task.id}">Save</button>
                        <button class="btn btn-sm btn-secondary cancel-edit">Cancel</button>
                    </div>
                </div>
            </li>
        `);
                $('#taskInput').val('');
                $('#taskFormContainer').slideUp(300);
            });
        });

        $(document).on('click', '.complete-btn', function() {
            let btn = $(this);
            let id = btn.data('id');
            let completed = btn.data('completed') === 1 ? 0 : 1;
            $.ajax({
                url: '/tasks/' + id,
                type: 'PUT',
                data: {
                    completed: completed
                },
                success: function(task) {
                    let item = btn.closest('li');
                    if (completed) {
                        item.addClass('completed');
                        btn.addClass('completed');
                    } else {
                        item.removeClass('completed');
                        btn.removeClass('completed');
                    }
                    btn.data('completed', completed);
                }
            });
        });

        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            $(this).closest('li').remove();
            $.ajax({
                url: '/tasks/' + id,
                type: 'DELETE'
            });
        });

        $(document).on('click', '.edit-btn', function() {
            $(this).closest('.task-content').hide();
            $(this).closest('li').find('.edit-form').show();
        });

        $(document).on('click', '.cancel-edit', function() {
            $(this).closest('.edit-form').hide();
            $(this).closest('li').find('.task-content').show();
        });

        $(document).on('click', '.save-edit', function() {
            let item = $(this).closest('li');
            let id = $(this).data('id');
            let title = item.find('.edit-title').val().trim();
            let desc = item.find('.edit-desc').val().trim() || null;
            if (!title) return alert('Please enter a title');
            $.ajax({
                url: '/tasks/' + id + '/edit',
                type: 'PUT',
                data: {
                    title: title,
                    description: desc
                },
                success: function(task) {
                    item.find('.task-title').text(task.title);
                    let descEl = item.find('.task-desc');
                    if (task.description) {
                        if (descEl.length) {
                            descEl.text(task.description);
                        } else {
                            item.find('.task-title').after('<div class="task-desc">' + task
                                .description + '</div>');
                        }
                    } else {
                        descEl.remove();
                    }
                    item.find('.edit-form').hide();
                    item.find('.task-content').show();
                }
            });
        });

        $('#searchInput').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $('#taskList .task-item').each(function() {
                let title = $(this).find('.task-title').text().toLowerCase();
                let desc = $(this).find('.task-desc')?.text().toLowerCase() || '';
                $(this).toggle(title.includes(value) || desc.includes(value));
            });
        });

        $('#openTaskForm').on('click', function() {
            $('#taskFormContainer').slideDown(400);
            $('#taskInput').focus();
        });

        $('#cancelForm').on('click', function() {
            $('#taskFormContainer').slideUp(300);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#datePicker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    </script>
</body>

</html>
