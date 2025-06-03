import sys
import json
import requests
from PyQt5.QtWidgets import (QApplication, QWidget, QPushButton, QVBoxLayout, 
                             QLineEdit, QTextEdit, QLabel, QMenu, QMessageBox)
from PyQt5.QtCore import Qt, QPoint
from PyQt5.QtGui import QPainter, QColor, QBrush


class DraggableButton(QPushButton):
    def __init__(self, parent=None):
        super().__init__(parent)
        self.setFixedSize(100, 100)
        self.setStyleSheet("""
            QPushButton {
                background-color: #4CAF50;
                border-radius: 50px;
                color: white;
                font-weight: bold;
                border: 2px solid #2E7D32;
            }
            QPushButton:hover {
                background-color: #66BB6A;
            }
            QPushButton:pressed {
                background-color: #2E7D32;
            }
        """)
        self.setText("Оставить\nзаявку")
        self.setCursor(Qt.PointingHandCursor)
        self._drag_pos = QPoint()

    def mousePressEvent(self, event):
        if event.button() == Qt.LeftButton:
            self._drag_pos = event.globalPos() - self.parent().frameGeometry().topLeft()
            event.accept()
        super().mousePressEvent(event)

    def mouseMoveEvent(self, event):
        if event.buttons() == Qt.LeftButton:
            self.parent().move(event.globalPos() - self._drag_pos)
            event.accept()


class ApplicationForm(QWidget):
    def __init__(self):
        super().__init__()
        self.initUI()
        self.api_url = "https://example.com/api/submit"  

    def initUI(self):
        self.setWindowTitle('Отправить заявку')
        self.setGeometry(100, 100, 300, 300)
        self.setWindowFlags(Qt.FramelessWindowHint | Qt.WindowStaysOnTopHint)
        
        # Создаем круглую кнопку
        self.button = DraggableButton(self)
        self.button.clicked.connect(self.show_menu)
        
        # Меню
        self.menu = QMenu(self)
        self.menu.addAction("Оставить заявку", self.show_form)
        
        # Форма заявки (изначально скрыта)
        self.form_widget = QWidget(self)
        self.form_widget.setGeometry(0, 0, 300, 300)
        self.form_widget.hide()
        
        layout = QVBoxLayout(self.form_widget)
        
        self.subject_label = QLabel("Тема заявки:")
        self.subject_input = QLineEdit()
        
        self.message_label = QLabel("Сообщение:")
        self.message_input = QTextEdit()
        
        self.send_button = QPushButton("Отправить")
        self.send_button.clicked.connect(self.submit_form)
        
        self.close_button = QPushButton("Закрыть")
        self.close_button.clicked.connect(self.hide_form)
        
        layout.addWidget(self.subject_label)
        layout.addWidget(self.subject_input)
        layout.addWidget(self.message_label)
        layout.addWidget(self.message_input)
        layout.addWidget(self.send_button)
        layout.addWidget(self.close_button)
        
        self.form_widget.setLayout(layout)

    def show_menu(self):
        self.menu.exec_(self.button.mapToGlobal(self.button.rect().bottomLeft()))

    def show_form(self):
        self.button.hide()
        self.form_widget.show()
        self.resize(300, 300)

    def hide_form(self):
        self.form_widget.hide()
        self.button.show()
        self.resize(100, 100)
        self.adjustSize()

    def submit_form(self):
        subject = self.subject_input.text().strip()
        message = self.message_input.toPlainText().strip()
        
        if not subject or not message:
            QMessageBox.warning(self, "Ошибка", "Пожалуйста, заполните все поля")
            return
        
        data = {
            "subject": subject,
            "message": message
        }
        
        try:
            response = requests.post(
                self.api_url,
                json=data,
                headers={'Content-Type': 'application/json'}
            )
            
            if response.status_code == 200:
                QMessageBox.information(self, "Успех", "Заявка успешно отправлена!")
                self.subject_input.clear()
                self.message_input.clear()
                self.hide_form()
            else:
                QMessageBox.critical(self, "Ошибка", f"Ошибка при отправке: {response.text}")
        except Exception as e:
            QMessageBox.critical(self, "Ошибка", f"Не удалось подключиться к серверу: {str(e)}")

    def paintEvent(self, event):
        painter = QPainter(self)
        painter.setRenderHint(QPainter.Antialiasing)
        painter.setBrush(QBrush(QColor(240, 240, 240, 200)))
        painter.setPen(Qt.NoPen)
        painter.drawRoundedRect(self.rect(), 15, 15)


if __name__ == '__main__':
    app = QApplication(sys.argv)
    ex = ApplicationForm()
    ex.show()
    sys.exit(app.exec_())