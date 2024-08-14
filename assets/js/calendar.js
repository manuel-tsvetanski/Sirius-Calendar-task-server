import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import bootstrapPlugin from '@fullcalendar/bootstrap';

// Correct FullCalendar CSS imports
import '@fullcalendar/daygrid/main.css'; // DayGrid plugin styles
import '@fullcalendar/timegrid/main.css'; // TimeGrid plugin styles
import '@fullcalendar/bootstrap/main.css'; // Bootstrap plugin styles

import 'bootstrap/dist/css/bootstrap.min.css'; // Bootstrap CSS

// Your custom calendar logic
document.addEventListener('DOMContentLoaded', function() {
    console.log('test');
    var calendarEl = document.getElementById('calendar');
    console.log('calendarEl:', calendarEl);

    if (calendarEl) {
        var eventsArray = JSON.parse(document.getElementById('calendar-data').textContent);
        console.log('eventsArray:', eventsArray);

        var calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, bootstrapPlugin],
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridDay,timeGridWeek,dayGridMonth'
            },
            timeZone: 'local',
            events: eventsArray,
            slotDuration: '01:00:00',
            allDaySlot: false,
            selectable: true,
            eventClick: function(info) {
                var modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
                var modalBody = document.getElementById('modalEventDetailsBody');

                if (info.event.url === '#') {
                    var user = info.event.extendedProps.user;
                    var userInfo = user ? `<p><strong>Name:</strong> ${user.name}</p><p><strong>Email:</strong> ${user.email}</p>` : '<p>No user details available</p>';

                    var deleteButton = user.appointmentId ? `<button type="button" class="btn btn-danger" id="deleteAppointment" data-id="${user.appointmentId}">Delete Appointment</button>` : '';

                    modalBody.innerHTML = `
                        <p><strong>Event:</strong> ${info.event.title}</p>
                        <p><strong>Start:</strong> ${info.event.start.toLocaleString()}</p>
                        ${userInfo}
                        ${deleteButton}
                    `;

                    if (user.appointmentId) {
                        document.getElementById('deleteAppointment').addEventListener('click', function() {
                            var appointmentId = this.getAttribute('data-id');
                            showDeleteConfirmation(appointmentId);
                        });
                    }
                } else {
                    loadFormIntoModal(info.event.url, modal, modalBody);
                }

                modal.show();
                info.jsEvent.preventDefault();
            }
        });

        calendar.render();
    }

    function loadFormIntoModal(url, modal, modalBody) {
        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalBody.innerHTML = html;
                attachFormSubmitHandler(modalBody, modal, url);
            })
            .catch(err => {
                modalBody.innerHTML = '<p>Error loading form. Please try again later.</p>';
            });
    }

    function attachFormSubmitHandler(modalBody, modal, url) {
        var form = modalBody.querySelector('form');
        if (!form) return;

        form.action = url;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(form);

            fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.ok && response.redirected) {
                        return response.text().then(data => {
                            if (data === 'success') {
                                location.reload();
                            }
                        });
                    }
                    return response.text();
                })
                .then(data => {
                    if (data === 'success') {
                        location.reload();
                    } else {
                        modalBody.innerHTML = data;
                        if (modalBody.querySelector('.form-error')) {
                            attachFormSubmitHandler(modalBody, modal, url);
                        }
                    }
                })
                .catch(err => {
                    modalBody.innerHTML = '<p>Error submitting form. Please try again later.</p>';
                });
        });
    }

    function showDeleteConfirmation(appointmentId) {
        var modalElement = document.createElement('div');
        modalElement.innerHTML = `
            <div class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this appointment?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">Yes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modalElement);

        var confirmationModal = new bootstrap.Modal(modalElement.querySelector('.modal'));
        confirmationModal.show();

        document.getElementById('confirmDelete').addEventListener('click', function() {
            console.log('Delete appointment with ID:', appointmentId);

            fetch(`/appointment/delete/${appointmentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        confirmationModal.hide();
                        location.reload();
                    } else {
                        confirmationModal._element.querySelector('.modal-body').innerHTML = '<p>Error deleting appointment. Please try again later.</p>';
                    }
                })
                .catch(err => {
                    confirmationModal._element.querySelector('.modal-body').innerHTML = '<p>Error deleting appointment. Please try again later.</p>';
                });
        });
    }
});