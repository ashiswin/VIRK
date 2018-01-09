import sys
import resources
from time import sleep
from subprocess import call
from PyQt5 import uic, QtCore
from PyQt5.QtWidgets import QApplication, QMainWindow, QLabel, QMessageBox, QProgressDialog
from PyQt5.QtGui import QPixmap, QIcon

class LogoutWindow(QMainWindow):
	def __init__(self, eventId):
		super(LogoutWindow, self).__init__()
		uic.loadUi('logoutwindow.ui', self)
		self.setWindowFlags(QtCore.Qt.Widget | QtCore.Qt.FramelessWindowHint);
		self.btnBack.clicked.connect(self.back)
		self.btnConfirm.clicked.connect(self.logout)
		self.btnKeyboard.clicked.connect(self.showKeyboard)
	def showKeyboard(self):
		print "showing keyboard"
		call("matchbox-keyboard") # Launch virtual keyboard
	def back(self):
		# Switch to waiting window
		global waitingWindow
		waitingWindow.show()
		self.hide()
	def logout(self):
		print self.txtPassword.text()
		
		# Check if logout credentials are valid
		if self.txtPassword.text() == "":
			msgBox = QMessageBox()
			msgBox.setWindowTitle("Error")
			msgBox.setText("Please enter a password")
			msgBox.addButton(QMessageBox.Ok)
			msgBox.exec_()
			return
		# TODO: Check with server if password is correct
		
		# Switch to login window
		global mainWindow
		mainWindow = MainWindow()
		mainWindow.show()
		self.hide()