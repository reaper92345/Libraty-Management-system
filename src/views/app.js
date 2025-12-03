document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = 'http://localhost/api';

    // Get DOM elements
    const authSection = document.getElementById('auth-section');
    const loginForm = document.getElementById('login-form');
    const loginEmailInput = document.getElementById('login-email');
    const loginPasswordInput = document.getElementById('login-password');
    const loginMessage = document.getElementById('login-message');
    const loginBtn = document.getElementById('login-btn');
    const logoutBtn = document.getElementById('logout-btn');

    const navHome = document.getElementById('nav-home');
    const navDashboard = document.getElementById('nav-dashboard');
    const navAdminBooks = document.getElementById('nav-admin-books');
    const navAdminUsers = document.getElementById('nav-admin-users');
    const navAdminTransactions = document.getElementById('nav-admin-transactions');
    const navAdminReports = document.getElementById('nav-admin-reports');

    const searchBrowseSection = document.getElementById('search-browse-section');
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const bookList = document.getElementById('book-list');

    const personalDashboardSection = document.getElementById('personal-dashboard-section');
    const dashboardFullName = document.getElementById('dashboard-full-name');
    const dashboardMemberId = document.getElementById('dashboard-member-id');
    const dashboardEmail = document.getElementById('dashboard-email');
    const dashboardRole = document.getElementById('dashboard-role');
    const borrowedBooksList = document.querySelector('#borrowed-books-list tbody');
    const borrowingHistoryList = document.querySelector('#borrowing-history-list tbody');
    const pendingReservationsList = document.querySelector('#pending-reservations-list tbody');

    const adminBooksSection = document.getElementById('admin-books-section');
    const adminUsersSection = document.getElementById('admin-users-section');
    const adminTransactionsSection = document.getElementById('admin-transactions-section');
    const adminReportsSection = document.getElementById('admin-reports-section');
    const overdueBooksReport = document.querySelector('#overdue-books-report tbody');
    const popularBooksReport = document.querySelector('#popular-books-report tbody');
    const totalMembersReport = document.getElementById('total-members-report');

    // Admin Book Management Elements
    const addBookBtn = document.getElementById('add-book-btn');
    const bookFormContainer = document.getElementById('book-form-container');
    const bookFormTitle = document.getElementById('book-form-title');
    const bookForm = document.getElementById('book-form');
    const bookIdInput = document.getElementById('book-id');
    const bookTitleInput = document.getElementById('book-title');
    const bookAuthorInput = document.getElementById('book-author');
    const bookIsbnInput = document.getElementById('book-isbn');
    const bookPublicationYearInput = document.getElementById('book-publication-year');
    const bookCategoryInput = document.getElementById('book-category');
    const bookTotalCopiesInput = document.getElementById('book-total-copies');
    const cancelBookFormBtn = document.getElementById('cancel-book-form-btn');
    const bookFormMessage = document.getElementById('book-form-message');
    const adminBookList = document.querySelector('#admin-book-list tbody');

    // Admin User Management Elements
    const addUserBtn = document.getElementById('add-user-btn');
    const userFormContainer = document.getElementById('user-form-container');
    const userFormTitle = document.getElementById('user-form-title');
    const userForm = document.getElementById('user-form');
    const userIdInput = document.getElementById('user-id');
    const userMemberIdInput = document.getElementById('user-member-id');
    const userFullNameInput = document.getElementById('user-full-name');
    const userEmailInput = document.getElementById('user-email');
    const userPhoneInput = document.getElementById('user-phone');
    const userPasswordInput = document.getElementById('user-password');
    const userRoleInput = document.getElementById('user-role');
    const userAccountStatusInput = document.getElementById('user-account-status');
    const cancelUserFormBtn = document.getElementById('cancel-user-form-btn');
    const userFormMessage = document.getElementById('user-form-message');
    const adminUserList = document.querySelector('#admin-user-list tbody');

    // Admin Transaction Management Elements
    const borrowBookForm = document.getElementById('borrow-book-form');
    const borrowBarcodeInput = document.getElementById('borrow-barcode');
    const borrowUserIdInput = document.getElementById('borrow-user-id');
    const borrowMessage = document.getElementById('borrow-message');

    const returnBookForm = document.getElementById('return-book-form');
    const returnTransactionIdInput = document.getElementById('return-transaction-id');
    const returnMessage = document.getElementById('return-message');

    const adminBookCopiesList = document.querySelector('#admin-book-copies-list tbody');
    const adminTransactionList = document.querySelector('#admin-transaction-list tbody');

    let currentUser = null;

    // --- UI Management Functions ---
    function showSection(section) {
        document.querySelectorAll('div[id$="-section"]').forEach(sec => {
            sec.style.display = 'none';
        });
        section.style.display = 'block';
    }

    function updateNavVisibility() {
        if (currentUser) {
            loginBtn.style.display = 'none';
            logoutBtn.style.display = 'block';
            navDashboard.style.display = 'block';

            if (currentUser.role === 'admin') {
                navAdminBooks.style.display = 'block';
                navAdminUsers.style.display = 'block';
                navAdminTransactions.style.display = 'block';
                navAdminReports.style.display = 'block';
            } else {
                navAdminBooks.style.display = 'none';
                navAdminUsers.style.display = 'none';
                navAdminTransactions.style.display = 'none';
                navAdminReports.style.display = 'none';
            }
            showSection(searchBrowseSection); // Default to search/browse after login
        } else {
            loginBtn.style.display = 'block';
            logoutBtn.style.display = 'none';
            navDashboard.style.display = 'none';
            navAdminBooks.style.display = 'none';
            navAdminUsers.style.display = 'none';
            navAdminTransactions.style.display = 'none';
            navAdminReports.style.display = 'none';
            showSection(authSection);
        }
    }

    // --- Authentication ---
    async function login(email, password) {
        try {
            const response = await fetch(`${API_BASE_URL}/users/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });
            const data = await response.json();
            if (response.ok) {
                currentUser = data.user;
                localStorage.setItem('currentUser', JSON.stringify(currentUser));
                loginMessage.textContent = '';
                updateNavVisibility();
                loadBooks(); // Load books after successful login
            } else {
                loginMessage.textContent = data.message || 'Login failed.';
            }
        } catch (error) {
            console.error('Login error:', error);
            loginMessage.textContent = 'An error occurred during login.';
        }
    }

    function logout() {
        currentUser = null;
        localStorage.removeItem('currentUser');
        updateNavVisibility();
    }

    // --- Book Management (Patron View) ---
    async function loadBooks(searchTerm = '') {
        bookList.innerHTML = '<div class="col">Loading books...</div>';
        try {
            const url = searchTerm ? `${API_BASE_URL}/books?s=${encodeURIComponent(searchTerm)}` : `${API_BASE_URL}/books`;
            const response = await fetch(url);
            const data = await response.json();

            bookList.innerHTML = ''; // Clear previous listings
            if (response.ok && data.records && data.records.length > 0) {
                data.records.forEach(book => {
                    const bookCard = `
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">${book.title}</h5>
                                    <h6 class="card-subtitle mb-2 text-muted">${book.author}</h6>
                                    <p class="card-text">ISBN: ${book.isbn}</p>
                                    <p class="card-text">Category: ${book.category || 'N/A'}</p>
                                    <p class="card-text">Publication Year: ${book.publication_year || 'N/A'}</p>
                                    <p class="card-text">Available Copies: ${book.available_copies}/${book.total_copies}</p>
                                    ${currentUser && currentUser.role === 'patron' && book.available_copies === 0 ?
                                        `<button class="btn btn-primary btn-sm mt-2 reserve-book-btn" data-book-id="${book.id}">Reserve</button>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    bookList.innerHTML += bookCard;
                });
                // Add event listeners for reserve buttons
                document.querySelectorAll('.reserve-book-btn').forEach(button => {
                    button.addEventListener('click', handleReserveBook);
                });
            } else {
                bookList.innerHTML = '<div class="col">No books found.</div>';
            }
        } catch (error) {
            console.error('Error loading books:', error);
            bookList.innerHTML = '<div class="col text-danger">Failed to load books.</div>';
        }
    }

    // --- Personal Dashboard ---
    async function loadDashboard() {
        if (!currentUser) {
            showSection(authSection);
            return;
        }

        dashboardFullName.textContent = currentUser.full_name;
        dashboardMemberId.textContent = currentUser.member_id;
        dashboardEmail.textContent = currentUser.email;
        dashboardRole.textContent = currentUser.role;

        borrowedBooksList.innerHTML = '<tr><td colspan="6">Loading borrowed books...</td></tr>';
        borrowingHistoryList.innerHTML = '<tr><td colspan="8">Loading borrowing history...</td></tr>';
        pendingReservationsList.innerHTML = '<tr><td colspan="5">Loading pending reservations...</td></tr>';

        try {
            // Fetch user-specific transactions
            const response = await fetch(`${API_BASE_URL}/transactions/user/${currentUser.id}`);
            const data = await response.json();

            borrowedBooksList.innerHTML = '';
            borrowingHistoryList.innerHTML = '';

            if (response.ok && data.records && data.records.length > 0) {
                data.records.forEach(transaction => {
                    const row = `
                        <tr>
                            <td>${transaction.book_title}</td>
                            <td>${transaction.book_author}</td>
                            <td>${transaction.barcode}</td>
                            <td>${transaction.borrow_date}</td>
                            <td>${transaction.due_date}</td>
                            <td>${transaction.status}</td>
                            ${transaction.return_date ? `<td>${transaction.return_date}</td>` : '<td>N/A</td>'}
                            ${transaction.fine_amount > 0 ? `<td>$${parseFloat(transaction.fine_amount).toFixed(2)}</td>` : '<td>N/A</td>'}
                        </tr>
                    `;
                    if (transaction.status === 'borrowed' || transaction.status === 'overdue') {
                        borrowedBooksList.innerHTML += row;
                    } else {
                        borrowingHistoryList.innerHTML += row;
                    }
                });
            } else {
                borrowedBooksList.innerHTML = '<tr><td colspan="6">No books currently borrowed.</td></tr>';
                borrowingHistoryList.innerHTML = '<tr><td colspan="8">No borrowing history.</td></tr>';
            }

            // Fetch pending reservations
            const reservationResponse = await fetch(`${API_BASE_URL}/reservations/user/${currentUser.id}`);
            const reservationData = await reservationResponse.json();

            pendingReservationsList.innerHTML = '';
            if (reservationResponse.ok && reservationData.records && reservationData.records.length > 0) {
                reservationData.records.forEach(reservation => {
                    const row = `
                        <tr>
                            <td>${reservation.book_title}</td>
                            <td>${reservation.book_author}</td>
                            <td>${reservation.reservation_date}</td>
                            <td>${reservation.status}</td>
                            <td>
                                <button class="btn btn-warning btn-sm cancel-reservation-btn" data-reservation-id="${reservation.id}">Cancel</button>
                            </td>
                        </tr>
                    `;
                    pendingReservationsList.innerHTML += row;
                });
                document.querySelectorAll('.cancel-reservation-btn').forEach(button => {
                    button.addEventListener('click', handleCancelReservation);
                });
            } else {
                pendingReservationsList.innerHTML = '<tr><td colspan="5">No pending reservations.</td></tr>';
            }

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            borrowedBooksList.innerHTML = '<tr><td colspan="6" class="text-danger">Failed to load borrowed books.</td></tr>';
            borrowingHistoryList.innerHTML = '<tr><td colspan="8" class="text-danger">Failed to load borrowing history.</td></tr>';
            pendingReservationsList.innerHTML = '<tr><td colspan="5" class="text-danger">Failed to load pending reservations.</td></tr>';
        }
    }

    // --- Admin Reports ---
    async function loadReports() {
        if (!currentUser || currentUser.role !== 'admin') {
            showSection(authSection);
            return;
        }

        overdueBooksReport.innerHTML = '<tr><td colspan="6">Loading overdue books...</td></tr>';
        popularBooksReport.innerHTML = '<tr><td colspan="4">Loading popular books...</td></tr>';
        totalMembersReport.textContent = 'Loading...';

        try {
            // Fetch Overdue Books
            const overdueResponse = await fetch(`${API_BASE_URL}/reports/overdue`);
            const overdueData = await overdueResponse.json();

            overdueBooksReport.innerHTML = '';
            if (overdueResponse.ok && overdueData.records && overdueData.records.length > 0) {
                overdueData.records.forEach(book => {
                    const row = `
                        <tr>
                            <td>${book.book_title}</td>
                            <td>${book.member_name}</td>
                            <td>${book.barcode}</td>
                            <td>${book.due_date}</td>
                            <td>${book.overdue_days}</td>
                            <td>$${parseFloat(book.fine_amount).toFixed(2)}</td>
                        </tr>
                    `;
                    overdueBooksReport.innerHTML += row;
                });
            } else {
                overdueBooksReport.innerHTML = '<tr><td colspan="6">No overdue books found.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading overdue books report:', error);
            overdueBooksReport.innerHTML = '<tr><td colspan="6" class="text-danger">Failed to load overdue books.</td></tr>';
        }

        try {
            // Fetch Popular Books
            const popularResponse = await fetch(`${API_BASE_URL}/reports/popular-books`);
            const popularData = await popularResponse.json();

            popularBooksReport.innerHTML = '';
            if (popularResponse.ok && popularData.records && popularData.records.length > 0) {
                popularData.records.forEach(book => {
                    const row = `
                        <tr>
                            <td>${book.title}</td>
                            <td>${book.author}</td>
                            <td>${book.isbn}</td>
                            <td>${book.borrow_count}</td>
                        </tr>
                    `;
                    popularBooksReport.innerHTML += row;
                });
            } else {
                popularBooksReport.innerHTML = '<tr><td colspan="4">No popular books found.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading popular books report:', error);
            popularBooksReport.innerHTML = '<tr><td colspan="4" class="text-danger">Failed to load popular books.</td></tr>';
        }

        try {
            // Fetch Total Members
            const membersResponse = await fetch(`${API_BASE_URL}/reports/total-members`);
            const membersData = await membersResponse.json();

            if (membersResponse.ok && membersData.total_members !== undefined) {
                totalMembersReport.textContent = `Total Members: ${membersData.total_members}`;
            } else {
                totalMembersReport.textContent = 'Unable to fetch total members.';
            }
        } catch (error) {
            console.error('Error loading total members report:', error);
            totalMembersReport.textContent = 'Failed to load total members.';
        }
    }

    // --- Reservation Management ---
    async function handleReserveBook(event) {
        const bookId = event.target.dataset.bookId;
        if (!currentUser || !bookId) return;

        try {
            const response = await fetch(`${API_BASE_URL}/reservations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ book_id: bookId, user_id: currentUser.id })
            });
            const data = await response.json();
            if (response.ok) {
                alert(data.message);
                loadBooks(); // Reload books to update status
                if (document.getElementById('personal-dashboard-section').style.display === 'block') {
                    loadDashboard(); // Reload dashboard if visible
                }
            } else {
                alert(data.message || 'Failed to reserve book.');
            }
        } catch (error) {
            console.error('Error reserving book:', error);
            alert('An error occurred while reserving the book.');
        }
    }

    async function handleCancelReservation(event) {
        const reservationId = event.target.dataset.reservationId;
        if (!currentUser || !reservationId) return;

        if (!confirm('Are you sure you want to cancel this reservation?')) {
            return;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/reservations/${reservationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();
            if (response.ok) {
                alert(data.message);
                loadDashboard(); // Reload dashboard to update reservations
            } else {
                alert(data.message || 'Failed to cancel reservation.');
            }
        } catch (error) {
            console.error('Error canceling reservation:', error);
            alert('An error occurred while canceling the reservation.');
        }
    }

    // --- Admin Book Management ---
    function showBookForm(book = null) {
        bookForm.reset();
        bookFormMessage.textContent = '';
        if (book) {
            bookFormTitle.textContent = 'Edit';
            bookIdInput.value = book.id;
            bookTitleInput.value = book.title;
            bookAuthorInput.value = book.author;
            bookIsbnInput.value = book.isbn;
            bookPublicationYearInput.value = book.publication_year;
            bookCategoryInput.value = book.category;
            bookTotalCopiesInput.value = book.total_copies;
            bookTotalCopiesInput.readOnly = true; // Prevent changing total copies directly on edit
        } else {
            bookFormTitle.textContent = 'Add';
            bookIdInput.value = '';
            bookTotalCopiesInput.readOnly = false;
        }
        bookFormContainer.style.display = 'block';
    }

    function hideBookForm() {
        bookFormContainer.style.display = 'none';
    }

    async function loadAdminBooks() {
        if (!currentUser || currentUser.role !== 'admin') {
            showSection(authSection);
            return;
        }

        adminBookList.innerHTML = '<tr><td colspan="8">Loading books...</td></tr>';
        try {
            const response = await fetch(`${API_BASE_URL}/books`);
            const data = await response.json();

            adminBookList.innerHTML = '';
            if (response.ok && data.records && data.records.length > 0) {
                data.records.forEach(book => {
                    const row = `
                        <tr>
                            <td>${book.id}</td>
                            <td>${book.title}</td>
                            <td>${book.author}</td>
                            <td>${book.isbn}</td>
                            <td>${book.category || 'N/A'}</td>
                            <td>${book.total_copies}</td>
                            <td>${book.available_copies}</td>
                            <td>
                                <button class="btn btn-sm btn-info edit-book-btn me-2" data-book-id="${book.id}">Edit</button>
                                <button class="btn btn-sm btn-danger delete-book-btn" data-book-id="${book.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                    adminBookList.innerHTML += row;
                });

                document.querySelectorAll('.edit-book-btn').forEach(button => {
                    button.addEventListener('click', handleEditBookClick);
                });
                document.querySelectorAll('.delete-book-btn').forEach(button => {
                    button.addEventListener('click', handleDeleteBook);
                });
            } else {
                adminBookList.innerHTML = '<tr><td colspan="8">No books found.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading admin books:', error);
            adminBookList.innerHTML = '<tr><td colspan="8" class="text-danger">Failed to load books.</td></tr>';
        }
    }

    async function handleBookFormSubmit(event) {
        event.preventDefault();

        const id = bookIdInput.value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `${API_BASE_URL}/books/${id}` : `${API_BASE_URL}/books`;

        const bookData = {
            title: bookTitleInput.value,
            author: bookAuthorInput.value,
            isbn: bookIsbnInput.value,
            publication_year: bookPublicationYearInput.value || null,
            category: bookCategoryInput.value || null,
            total_copies: parseInt(bookTotalCopiesInput.value),
            // available_copies is handled by backend on create, or adjusted via book copies
            // For update, we assume available_copies might be updated via book copy transactions, not directly here.
            // If total_copies is updated on edit, available_copies might need a more complex logic.
            // For simplicity, we'll only send total_copies and let backend adjust available_copies on new book creation.
            // For existing books, available_copies changes through borrowing/returning/adding physical copies.
        };

        if (method === 'PUT') {
            // When updating, we need to send the current available_copies too, or let backend handle it.
            // For now, let's fetch the existing book to get available_copies if it's not a new book
            const existingBookResponse = await fetch(`${API_BASE_URL}/books/${id}`);
            const existingBookData = await existingBookResponse.json();
            if (existingBookResponse.ok) {
                bookData.available_copies = existingBookData.available_copies;
            }
        }

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(bookData)
            });
            const data = await response.json();

            if (response.ok) {
                bookFormMessage.textContent = data.message;
                bookFormMessage.style.color = 'green';
                hideBookForm();
                loadAdminBooks();
            } else {
                bookFormMessage.textContent = data.message || `Failed to ${id ? 'update' : 'add'} book.`;
                bookFormMessage.style.color = 'red';
            }
        } catch (error) {
            console.error(`Error ${id ? 'updating' : 'adding'} book:`, error);
            bookFormMessage.textContent = `An error occurred while ${id ? 'updating' : 'adding'} the book.`;
            bookFormMessage.style.color = 'red';
        }
    }

    async function handleEditBookClick(event) {
        const bookId = event.target.dataset.bookId;
        try {
            const response = await fetch(`${API_BASE_URL}/books/${bookId}`);
            const book = await response.json();
            if (response.ok) {
                showBookForm(book);
            } else {
                alert(book.message || 'Failed to fetch book for editing.');
            }
        } catch (error) {
            console.error('Error fetching book for edit:', error);
            alert('An error occurred while fetching book details.');
        }
    }

    async function handleDeleteBook(event) {
        const bookId = event.target.dataset.bookId;
        if (!confirm('Are you sure you want to delete this book? This will also delete all associated physical copies and transactions.')) {
            return;
        }
        try {
            const response = await fetch(`${API_BASE_URL}/books/${bookId}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            if (response.ok) {
                alert(data.message);
                loadAdminBooks();
            } else {
                alert(data.message || 'Failed to delete book.');
            }
        } catch (error) {
            console.error('Error deleting book:', error);
            alert('An error occurred while deleting the book.');
        }
    }

    // --- Admin User Management ---
    function showUserForm(user = null) {
        userForm.reset();
        userFormMessage.textContent = '';
        if (user) {
            userFormTitle.textContent = 'Edit';
            userIdInput.value = user.id;
            userMemberIdInput.value = user.member_id;
            userFullNameInput.value = user.full_name;
            userEmailInput.value = user.email;
            userPhoneInput.value = user.phone;
            userRoleInput.value = user.role;
            userAccountStatusInput.value = user.account_status;
            userPasswordInput.removeAttribute('required'); // Password not required on edit
        } else {
            userFormTitle.textContent = 'Add';
            userIdInput.value = '';
            userPasswordInput.setAttribute('required', 'required');
        }
        userFormContainer.style.display = 'block';
    }

    function hideUserForm() {
        userFormContainer.style.display = 'none';
    }

    async function loadAdminUsers() {
        if (!currentUser || currentUser.role !== 'admin') {
            showSection(authSection);
            return;
        }

        adminUserList.innerHTML = '<tr><td colspan="8">Loading users...</td></tr>';
        try {
            const response = await fetch(`${API_BASE_URL}/users`);
            const data = await response.json();

            adminUserList.innerHTML = '';
            if (response.ok && data.records && data.records.length > 0) {
                data.records.forEach(user => {
                    const row = `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.member_id}</td>
                            <td>${user.full_name}</td>
                            <td>${user.email}</td>
                            <td>${user.phone || 'N/A'}</td>
                            <td>${user.role}</td>
                            <td>${user.account_status}</td>
                            <td>
                                <button class="btn btn-sm btn-info edit-user-btn me-2" data-user-id="${user.id}">Edit</button>
                                <button class="btn btn-sm btn-danger delete-user-btn" data-user-id="${user.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                    adminUserList.innerHTML += row;
                });

                document.querySelectorAll('.edit-user-btn').forEach(button => {
                    button.addEventListener('click', handleEditUserClick);
                });
                document.querySelectorAll('.delete-user-btn').forEach(button => {
                    button.addEventListener('click', handleDeleteUser);
                });
            } else {
                adminUserList.innerHTML = '<tr><td colspan="8">No users found.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading admin users:', error);
            adminUserList.innerHTML = '<tr><td colspan="8" class="text-danger">Failed to load users.</td></tr>';
        }
    }

    async function handleUserFormSubmit(event) {
        event.preventDefault();

        const id = userIdInput.value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `${API_BASE_URL}/users/${id}` : `${API_BASE_URL}/users`;

        const userData = {
            member_id: userMemberIdInput.value,
            full_name: userFullNameInput.value,
            email: userEmailInput.value,
            phone: userPhoneInput.value || null,
            role: userRoleInput.value,
            account_status: userAccountStatusInput.value,
        };

        if (userPasswordInput.value) {
            userData.password = userPasswordInput.value;
        }

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });
            const data = await response.json();

            if (response.ok) {
                userFormMessage.textContent = data.message;
                userFormMessage.style.color = 'green';
                hideUserForm();
                loadAdminUsers();
            } else {
                userFormMessage.textContent = data.message || `Failed to ${id ? 'update' : 'add'} user.`;
                userFormMessage.style.color = 'red';
            }
        } catch (error) {
            console.error(`Error ${id ? 'updating' : 'adding'} user:`, error);
            userFormMessage.textContent = `An error occurred while ${id ? 'updating' : 'adding'} the user.`;
            userFormMessage.style.color = 'red';
        }
    }

    async function handleEditUserClick(event) {
        const userId = event.target.dataset.userId;
        try {
            const response = await fetch(`${API_BASE_URL}/users/${userId}`);
            const user = await response.json();
            if (response.ok) {
                showUserForm(user);
            } else {
                alert(user.message || 'Failed to fetch user for editing.');
            }
        } catch (error) {
            console.error('Error fetching user for edit:', error);
            alert('An error occurred while fetching user details.');
        }
    }

    async function handleDeleteUser(event) {
        const userId = event.target.dataset.userId;
        if (!confirm('Are you sure you want to delete this user? This will also affect their borrowed books and reservations.')) {
            return;
        }
        try {
            const response = await fetch(`${API_BASE_URL}/users/${userId}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            if (response.ok) {
                alert(data.message);
                loadAdminUsers();
            } else {
                alert(data.message || 'Failed to delete user.');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            alert('An error occurred while deleting the user.');
        }
    }

    // --- Admin Transaction Management ---
    async function loadAdminTransactions() {
        if (!currentUser || currentUser.role !== 'admin') {
            showSection(authSection);
            return;
        }

        borrowMessage.textContent = '';
        returnMessage.textContent = '';
        adminBookCopiesList.innerHTML = '<tr><td colspan="5">Loading book copies...</td></tr>';
        adminTransactionList.innerHTML = '<tr><td colspan="9">Loading transactions...</td></tr>';

        try {
            // Fetch Book Copies
            const bookCopiesResponse = await fetch(`${API_BASE_URL}/bookcopies`);
            const bookCopiesData = await bookCopiesResponse.json();

            adminBookCopiesList.innerHTML = '';
            if (bookCopiesResponse.ok && bookCopiesData.records && bookCopiesData.records.length > 0) {
                bookCopiesData.records.forEach(copy => {
                    const row = `
                        <tr>
                            <td>${copy.id}</td>
                            <td>${copy.book_id}</td>
                            <td>${copy.barcode}</td>
                            <td>${copy.status}</td>
                            <td>
                                <button class="btn btn-sm btn-danger delete-book-copy-btn" data-book-copy-id="${copy.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                    adminBookCopiesList.innerHTML += row;
                });
                document.querySelectorAll('.delete-book-copy-btn').forEach(button => {
                    button.addEventListener('click', handleDeleteBookCopy);
                });
            } else {
                adminBookCopiesList.innerHTML = '<tr><td colspan="5">No book copies found.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading book copies:', error);
            adminBookCopiesList.innerHTML = '<tr><td colspan="5" class="text-danger">Failed to load book copies.</td></tr>';
        }

        try {
            // Fetch All Transactions
            const transactionsResponse = await fetch(`${API_BASE_URL}/transactions`);
            const transactionsData = await transactionsResponse.json();

            adminTransactionList.innerHTML = '';
            if (transactionsResponse.ok && transactionsData.records && transactionsData.records.length > 0) {
                transactionsData.records.forEach(transaction => {
                    const row = `
                        <tr>
                            <td>${transaction.id}</td>
                            <td>${transaction.book_title}</td>
                            <td>${transaction.member_name}</td>
                            <td>${transaction.barcode}</td>
                            <td>${transaction.borrow_date}</td>
                            <td>${transaction.due_date}</td>
                            <td>${transaction.return_date || 'N/A'}</td>
                            <td>$${parseFloat(transaction.fine_amount).toFixed(2)}</td>
                            <td>${transaction.status}</td>
                        </tr>
                    `;
                    adminTransactionList.innerHTML += row;
                });
            } else {
                adminTransactionList.innerHTML = '<tr><td colspan="9">No transactions found.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
            adminTransactionList.innerHTML = '<tr><td colspan="9" class="text-danger">Failed to load transactions.</td></tr>';
        }
    }

    async function handleBorrowBook(event) {
        event.preventDefault();

        const bookCopyBarcode = borrowBarcodeInput.value;
        const userId = borrowUserIdInput.value;

        try {
            const response = await fetch(`${API_BASE_URL}/transactions/borrow`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ book_copy_barcode: bookCopyBarcode, user_id: userId })
            });
            const data = await response.json();

            if (response.ok) {
                borrowMessage.textContent = data.message;
                borrowMessage.style.color = 'green';
                borrowBookForm.reset();
                loadAdminTransactions();
                loadBooks(); // Update patron view
            } else {
                borrowMessage.textContent = data.message || 'Failed to borrow book.';
                borrowMessage.style.color = 'red';
            }
        } catch (error) {
            console.error('Error borrowing book:', error);
            borrowMessage.textContent = 'An error occurred while borrowing the book.';
            borrowMessage.style.color = 'red';
        }
    }

    async function handleReturnBook(event) {
        event.preventDefault();

        const transactionId = returnTransactionIdInput.value;

        try {
            const response = await fetch(`${API_BASE_URL}/transactions/return`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ transaction_id: transactionId })
            });
            const data = await response.json();

            if (response.ok) {
                returnMessage.textContent = data.message + (data.fine_amount > 0 ? ` Fine: $${parseFloat(data.fine_amount).toFixed(2)}` : '');
                returnMessage.style.color = 'green';
                returnBookForm.reset();
                loadAdminTransactions();
                loadBooks(); // Update patron view
            } else {
                returnMessage.textContent = data.message || 'Failed to return book.';
                returnMessage.style.color = 'red';
            }
        } catch (error) {
            console.error('Error returning book:', error);
            returnMessage.textContent = 'An error occurred while returning the book.';
            returnMessage.style.color = 'red';
        }
    }

    async function handleDeleteBookCopy(event) {
        const bookCopyId = event.target.dataset.bookCopyId;
        if (!confirm('Are you sure you want to delete this book copy? This will decrement the total and available copies of the associated book.')) {
            return;
        }
        try {
            const response = await fetch(`${API_BASE_URL}/bookcopies/${bookCopyId}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            if (response.ok) {
                alert(data.message);
                loadAdminTransactions();
                loadBooks(); // Update patron view
            } else {
                alert(data.message || 'Failed to delete book copy.');
            }
        } catch (error) {
            console.error('Error deleting book copy:', error);
            alert('An error occurred while deleting the book copy.');
        }
    }

    // --- Event Listeners ---
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        login(loginEmailInput.value, loginPasswordInput.value);
    });

    logoutBtn.addEventListener('click', logout);

    navHome.addEventListener('click', (e) => {
        e.preventDefault();
        showSection(searchBrowseSection);
        loadBooks();
    });

    navDashboard.addEventListener('click', (e) => {
        e.preventDefault();
        showSection(personalDashboardSection);
        loadDashboard();
    });

    navAdminBooks.addEventListener('click', (e) => {
        e.preventDefault();
        showSection(adminBooksSection);
        loadAdminBooks();
    });

    addBookBtn.addEventListener('click', () => showBookForm());
    cancelBookFormBtn.addEventListener('click', hideBookForm);
    bookForm.addEventListener('submit', handleBookFormSubmit);

    navAdminUsers.addEventListener('click', (e) => {
        e.preventDefault();
        showSection(adminUsersSection);
        loadAdminUsers();
    });

    addUserBtn.addEventListener('click', () => showUserForm());
    cancelUserFormBtn.addEventListener('click', hideUserForm);
    userForm.addEventListener('submit', handleUserFormSubmit);

    navAdminTransactions.addEventListener('click', (e) => {
        e.preventDefault();
        showSection(adminTransactionsSection);
        loadAdminTransactions();
    });

    borrowBookForm.addEventListener('submit', handleBorrowBook);
    returnBookForm.addEventListener('submit', handleReturnBook);

    navAdminReports.addEventListener('click', (e) => {
        e.preventDefault();
        showSection(adminReportsSection);
        loadReports();
    });

    searchButton.addEventListener('click', () => {
        loadBooks(searchInput.value);
    });

    // Initial load
    const storedUser = localStorage.getItem('currentUser');
    if (storedUser) {
        currentUser = JSON.parse(storedUser);
    }
    updateNavVisibility();
    if (currentUser) {
        loadBooks();
    }
});
