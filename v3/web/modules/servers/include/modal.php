<!-- Модальное окно добавления оборудования -->
<div id="addServerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Добавить оборудование</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addServerForm">
                <div class="form-group">
                    <label for="serverName">Наименование оборудования*</label>
                    <input type="text" id="serverName" name="serverName" maxlength="50" required 
                        pattern="[a-zA-Zа-яА-Я0-9\-_ ]+" 
                        title="Разрешены русские и латинские символы, цифры, тире и подчеркивание">
                </div>
                
                <div class="form-group">
                    <label for="serverStatus">Статус*</label>
                    <select id="serverStatus" name="serverStatus" required>
                        <option value="" selected disabled>Выберите статус</option>
                        <option value="Планирование">Планирование</option>
                        <option value="Установка">Установка</option>
                        <option value="Настройка">Настройка</option>
                        <option value="Опытная эксплуатация">Опытная эксплуатация</option>
                        <option value="Промышленная эксплуатация">Промышленная эксплуатация</option>
                        <option value="Резерв">Резерв</option>
                        <option value="Выведен из эксплуатации">Выведен из эксплуатации</option>
                        <option value="Списан">Списан</option>
                        <option value="Утилизирован">Утилизирован</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="ipv6Checkbox" name="ipv6Checkbox">
                        Использовать IPv6
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="ipAddress">IP Адрес</label>
                    <input type="text" id="ipAddress" name="ipAddress" maxlength="45" 
                        pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" 
                        title="Формат IPv4: xxx.xxx.xxx.xxx">
                </div>
                
                <div class="form-group">
                    <label for="domain">Домен</label>
                    <input type="text" id="domain" name="domain" maxlength="50">
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancelAddServer" class="cancel-btn">Отменить</button>
                    <button type="submit" id="createServer" class="create-btn">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>