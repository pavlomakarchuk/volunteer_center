// Функція для обробки подачі форм через AJAX
function handleFormSubmit(formId, actionUrl, successCallback, errorCallback) {
    document.getElementById(formId).addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        fetch(actionUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (successCallback) {
                    successCallback(data);
                }
                // Update form fields with the new data
                if (data.updatedVPO) {
                    const formFields = this.elements;
                    formFields['vpo_name'].value = data.updatedVPO.vpo_name;
                    formFields['replacement_date'].value = data.updatedVPO.replacement_date;
                    formFields['dovidka'].value = data.updatedVPO.dovidka;
                    formFields['region'].value = data.updatedVPO.region;
                    formFields['city'].value = data.updatedVPO.city;
                    formFields['mobile'].value = data.updatedVPO.mobile;
                    formFields['address'].value = data.updatedVPO.address;
                }
            } else {
                if (errorCallback) errorCallback(data.message);
                else alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (errorCallback) errorCallback(error);
            else alert('Сталася помилка. Спробуйте ще раз пізніше.');
        });
    });
}

// Функції для конкретних сторінок
document.addEventListener('DOMContentLoaded', function() {
    // Add VPO Form Submission
    if (document.getElementById('add-vpo-form')) {
        handleFormSubmit(
            'add-vpo-form', 
            'backend/backend.php?action=add_vpo',
            function (data) {
                alert('Переселенця успішно додано');
                document.getElementById('add-vpo-form').reset();
            }
        );
    }

    // Add News Form Submission
    if (document.getElementById('add-news-form')) {
        handleFormSubmit(
            'add-news-form', 
            'backend/backend.php?action=add_news',
            function (data) {
                alert('Новину успішно додано');
                document.getElementById('add-news-form').reset();
            }
        );
    }

    // Check Next Date Form Submission
    if (document.getElementById('check-date-form')) {
        handleFormSubmit(
            'check-date-form', 
            'backend/backend.php?action=check_next_date',
            function (data) {
                const nextDateInfo = document.getElementById('next-date-info');
                nextDateInfo.innerHTML = `
                    <h3>Наступна дата відвідування</h3>
                    <p>Коли ви можете прийти по допомогу: ${data.next_date}</p>
                `;
            },
            function (message) {
                const nextDateInfo = document.getElementById('next-date-info');
                nextDateInfo.innerHTML = `<p>Переселенця з такою довідкою не знайдено або він не може отримати допомогу найближчим часом.</p>`;
            }
        );
    }

    

    // Search VPO Form Submission
    if (document.getElementById('search-form')) {
        handleFormSubmit(
            'search-form', 
            'backend/backend.php?action=search_vpo',
            function (data) {
                const vpoInfoSection = document.getElementById('vpo-info');
                vpoInfoSection.innerHTML = '';
                
                if (data.success) {
                    const vpoInfo = document.createElement('div');
                    vpoInfo.innerHTML = `
                        <h3>Редагувати інформацію про переселенця</h3>
                        <form id="edit-vpo-form">
                            <label for="vpo_name">Прізвище, Ім'я, По-батькові:</label>
                            <input type="text" id="vpo_name" name="vpo_name" value="${data.vpo.vpo_name}" required>

                            <label for="replacement_date">Дата прибуття в місто:</label>
                            <input type="date" id="replacement_date" name="replacement_date" value="${data.vpo.replacement_date}" required>

                            <label for="dovidka">Номер довідки:</label>
                            <input type="text" id="dovidka" name="dovidka" value="${data.vpo.dovidka}" required>

                            <label for="region">Область:</label>
                            <input type="text" id="region" name="region" value="${data.vpo.region}" required>

                            <label for="city">Населений пункт:</label>
                            <input type="text" id="city" name="city" value="${data.vpo.city}" required>

                            <label for="mobile">Мобільний телефон:</label>
                            <input type="tel" id="mobile" name="mobile" value="${data.vpo.mobile}" required>

                            <label for="address">Адреса проживання:</label>
                            <input type="text" id="address" name="address" value="${data.vpo.address}" required>

                            <input type="hidden" name="vpo_id" value="${data.vpo.id}">

                            <button type="submit">Зберегти зміни</button>
                        </form>
                    `;

                    const assistanceForm = document.createElement('form');
                    assistanceForm.id = "assistance-form";

                    if (data.can_get_help) {
                        assistanceForm.innerHTML = `
                            <label for="next-date">Дата наступного візиту:</label>
                            <input type="date" id="next-date" name="next_date" value="${data.next_date_default}">
                            <input type="hidden" name="dovidka" value="${data.vpo.dovidka}">
                            <button type="submit">Допомогу отримано</button>
                        `;
                    } else {
                        assistanceForm.innerHTML = `<button type="button" disabled>Переселенець поки що не може отримати допомогу</button>`;
                    }

                    vpoInfoSection.appendChild(vpoInfo);
                    vpoInfoSection.appendChild(assistanceForm);

                    document.getElementById('edit-vpo-form').addEventListener('submit', function(event) {
                        event.preventDefault();

                        const formData = new FormData(this);
                        fetch('backend/backend.php?action=update_vpo', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('VPO information updated successfully');
                                // Update the form fields with the new data
                                this.elements['vpo_name'].value = data.updatedVPO.vpo_name;
                                this.elements['replacement_date'].value = data.updatedVPO.replacement_date;
                                this.elements['dovidka'].value = data.updatedVPO.dovidka;
                                this.elements['region'].value = data.updatedVPO.region;
                                this.elements['city'].value = data.updatedVPO.city;
                                this.elements['mobile'].value = data.updatedVPO.mobile;
                                this.elements['address'].value = data.updatedVPO.address;
                            } else {
                                alert('Error updating VPO information: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while updating VPO information. Please try again later.');
                        });
                    });

                    document.getElementById('assistance-form').addEventListener('submit', function(event) {
                        event.preventDefault();
                        
                        const formData = new FormData(this);
                        fetch('backend/backend.php?action=record_assistance', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Дані про видачу допомоги успішно збережені');
                                location.reload();
                            } else {
                                alert('Сталася помилка при збереженні даних');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Сталася помилка при збереженні даних.');
                        });
                    });
                } else {
                    vpoInfoSection.innerHTML = `<p>Переселенця не знайдено.</p>`;
                }
            }
        );
    }
});