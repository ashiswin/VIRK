import sys
import resources
from time import sleep
from subprocess import call
from PyQt5 import uic, QtCore
from PyQt5.QtWidgets import QApplication, QMainWindow, QLabel, QMessageBox, QProgressDialog
from PyQt5.QtGui import QPixmap, QIcon

class GroupLoadThread(QtCore.QThread):
    def __init__ (self, parentQWidget = None):
        super(GroupLoadThread, self).__init__(parentQWidget)
        self.wasCanceled = False
        self.parentQWidget = parentQWidget
    
    def run (self):
        # TODO: Load groups from server
        # Dummy data
        self.parentQWidget.groupNames = ['Select group...', 'IoT1', 'Lol', 'Hi']
        self.parentQWidget.groupIds = [0, 1, 2, 3]
        sleep(2)
        # Update group combo box
        self.parentQWidget.spnGroup.addItems(self.parentQWidget.groupNames)
        self.parentQWidget.spnGroup.setStyleSheet('''
        QComboBox { 
        selection-color: white; 
        selection-background-color: grey; 
        }''')
        self.parentQWidget.spnGroup.setEnabled(True)


class LoginThread(QtCore.QThread):
    success = QtCore.pyqtSignal(object)
    error = QtCore.pyqtSignal(object)
    
    def __init__(self, groupId, password, parentQWidget = None, ):
        super(LoginThread, self).__init__(parentQWidget)
        self.groupId = groupId
        self.password = password

    def run(self):
        # TODO: Check if login credentials are valid
        response = {"maxVotes":10, "rewardVotes":8}
        self.success.emit(response)

class LogoutThread(QtCore.QThread):
    success = QtCore.pyqtSignal()
    error = QtCore.pyqtSignal(object)
    
    def __init__(self, eventId, password, parentQWidget = None, ):
        super(LogoutThread, self).__init__(parentQWidget)
        self.eventId = eventId
        self.password = password

    def run(self):
        # TODO: Check if logout credentials are valid
        self.success.emit()
        pass

class NFCCheckThread(QtCore.QThread):
    idDetected = QtCore.pyqtSignal(str)
    
    def __init__ (self, parentQWidget = None):
        super(NFCCheckThread, self).__init__(parentQWidget)
        self.parentQWidget = parentQWidget
    
    def run(self):
        while True:
            # TODO: Actually check for student/staff card
            # Dummy event
            sleep(5)
            self.idDetected.emit("voterid")
            break
    
class MainWindow(QMainWindow):
    def __init__(self):
        super(MainWindow, self).__init__()
        uic.loadUi('mainwindow.ui', self)
        self.setWindowFlags(QtCore.Qt.Widget | QtCore.Qt.FramelessWindowHint)
        
        # TODO: Load events from server
        
        self.eventNames = ['Select event...', 'ISTD Showcase 2017', '3.007 Showcase 2017']
        self.eventIds = [0, 1, 2]
        
        self.spnEvent.addItems(self.eventNames)
        self.spnEvent.setStyleSheet('''
        QComboBox { 
        selection-color: white; 
        selection-background-color: grey; 
        }''')
        self.spnEvent.activated.connect(self.eventSelected) # Listen for selection of event
        self.btnKeyboard.clicked.connect(self.showKeyboard) # Listen for v-keyboard button press
        self.btnConfirm.clicked.connect(self.login) # Listen for confirm button press
        self.btnClose.clicked.connect(self.close) # Listen for close button press
        self.show()
    
    def eventSelected(self):
        print self.spnEvent.currentIndex(), self.spnEvent.currentText()
        
        groupThread = GroupLoadThread(self)
        self.progdialog = QProgressDialog("", "", 0, 0, self)
        self.progdialog.setCancelButton(None)
        self.progdialog.setLabel(None)
        self.progdialog.setStyleSheet("background-color: #FFFFFF")
        self.progdialog.setWindowTitle("Loading groups")
        self.progdialog.setModal(True)
        groupThread.finished.connect(self.progdialog.close)
        groupThread.start()
        self.progdialog.exec_()
    
    def showKeyboard(self):
        print "showing keyboard"
        call("matchbox-keyboard") # Launch virtual keyboard
    
    def login(self):
        print "Login attempted"    
        print self.spnGroup.currentIndex()
        print self.txtPassword.text()
        
        # Check if login credentials are valid
        if self.spnEvent.currentIndex() < 1:
            msgBox = QMessageBox()
            msgBox.setWindowTitle("Error")
            msgBox.setText("Please select an event")
            msgBox.addButton(QMessageBox.Ok)
            msgBox.exec_()
            return
        
        if self.spnGroup.currentIndex() < 1:
            msgBox = QMessageBox()
            msgBox.setWindowTitle("Error")
            msgBox.setText("Please select a group")
            msgBox.addButton(QMessageBox.Ok)
            msgBox.exec_()
            return
        
        if self.txtPassword.text() == "":
            msgBox = QMessageBox()
            msgBox.setWindowTitle("Error")
            msgBox.setText("Please enter a password")
            msgBox.addButton(QMessageBox.Ok)
            msgBox.exec_()
            return
        
        loginThread = LoginThread(self.groupIds[self.spnGroup.currentIndex()], self.txtPassword.text(), self)
        loginThread.success.connect(self.success)
        loginThread.error.connect(self.error)
        loginThread.start()

    def success(self, response):
        # Switch to waiting mode and prep all windows
        global waitingWindow
        global logoutWindow
        global votingWindow
        waitingWindow = WaitingWindow(self.spnGroup.currentText(), self.groupIds[self.spnGroup.currentIndex()], response["maxVotes"])
        logoutWindow = LogoutWindow(self.eventIds[self.spnEvent.currentIndex()])
        votingWindow = VotingWindow(self.spnGroup.currentText(), self.groupIds[self.spnGroup.currentIndex()], response["rewardVotes"])
        waitingWindow.show()
        self.hide()
    
    def error(self, response):
        msgBox = QMessageBox()
        msgBox.setWindowTitle("Error")
        msgBox.setText("Something went wrong: " + response.message)
        msgBox.addButton(QMessageBox.Ok)
        msgBox.exec_()
        return
    
    def close(self):
        msgBox = QMessageBox()
        msgBox.setWindowTitle("Close KIRAVoter")
        msgBox.setText("Do you really want to quit KIRAVoter?")
        msgBox.addButton(QMessageBox.Yes)
        msgBox.addButton(QMessageBox.No)
        ret = msgBox.exec_()
        if ret == QMessageBox.Yes:
            quit()
	    sys.exit(0)

class WaitingWindow(QMainWindow):
    def __init__(self, groupName, groupId, maxVotes):
        super(WaitingWindow, self).__init__()
        uic.loadUi('waitingwindow.ui', self)
        self.setWindowFlags(QtCore.Qt.Widget | QtCore.Qt.FramelessWindowHint);
        self.lblGroupName.setText(groupName)
        self.btnLogout.clicked.connect(self.logout)
        
        self.MAX_VOTES = maxVotes

        self.startChecking()
    
    def startChecking(self):
        self.checkerThread = NFCCheckThread(self)
        self.checkerThread.setTerminationEnabled(True)
        self.checkerThread.idDetected.connect(self.idDetected)
        self.checkerThread.start()
    
    def idDetected(self, voterId):
        # TODO: check if student has voted more than max votes
        votes = 0 # get votes from server
        
        if votes > self.MAX_VOTES:
            msgBox = QMessageBox()
            msgBox.setWindowTitle("Error")
            msgBox.setText("You have voted the maximum number of times")
            msgBox.addButton(QMessageBox.Ok)
            msgBox.exec_()
            self.checkerThread.start()
            return
        
        global votingWindow
        votingWindow.voterId = voterId
        self.checkerThread.terminate()
        votingWindow.show()
        self.hide()
    
    def logout(self):
        # Switch to logout window
        global logoutWindow
        
        try:
            logoutWindow.txtPassword.setText('')
        except:
            print 'Clearing txtPassword Error'
            
        logoutWindow.show()
        self.hide()

class VotingWindow(QMainWindow):
    def __init__(self, groupName, groupId, rewardVotes = 9999999):
        super(VotingWindow, self).__init__()
        uic.loadUi('votingwindow.ui', self)
        self.setWindowFlags(QtCore.Qt.Widget | QtCore.Qt.FramelessWindowHint);
        self.lblGroupName.setText(groupName)
        self.btnConfirm.clicked.connect(self.vote)
        self.voterId = None
        self.groupName = groupName
        self.groupId = groupId
        self.REWARD_VOTES = rewardVotes
        
        self.rating = 0
        self.buttons = list()
        self.buttons.append(self.btn1)
        self.buttons.append(self.btn2)
        self.buttons.append(self.btn3)
        self.buttons.append(self.btn4)
        self.buttons.append(self.btn5)
        
        # Code for voting buttons
        self.btn1.clicked.connect(lambda: self.setVote(1))
        self.btn2.clicked.connect(lambda: self.setVote(2))
        self.btn3.clicked.connect(lambda: self.setVote(3))
        self.btn4.clicked.connect(lambda: self.setVote(4))
        self.btn5.clicked.connect(lambda: self.setVote(5))
    
    def setVote(self, rating):
        print rating
        self.rating = rating
        for i in range(rating):
            self.buttons[i].setIcon(QIcon(QPixmap(":/images/assets/star-active.png")))
        for i in range(rating, len(self.buttons)):
            self.buttons[i].setIcon(QIcon(QPixmap(":/images/assets/star-normal.png")))
    
    def vote(self):
        if self.rating == 0:
            msgBox = QMessageBox()
            msgBox.setWindowTitle("Error")
            msgBox.setText("Please select your vote")
            msgBox.addButton(QMessageBox.Ok)
            msgBox.exec_()
            return
        
        print 'Vote received'
        # TODO: Send vote to server with student ID
        
        # TODO: Get number of votes and reward from server
        
        # Dummy data
        votes = 10
        reward = "free ice-cream"
        
        global thankyouWindow
        global waitingWindow
        if votes <= self.REWARD_VOTES:
            thankyouWindow.lblMessage.setText("Thank you for voting for " + self.groupName + "!")
        else:
            thankyouWindow.lblMessage.setText("Thank you for voting for " + self.groupName + "! Please proceed to the counter to collect your " + reward + "!")
        # Reset variables
        self.rating = 0
        for i in range(len(self.buttons)):
            self.buttons[i].setIcon(QIcon(QPixmap(":/images/assets/star-normal.png")))
        
        thankyouWindow.show()
        self.hide()
        timer = QtCore.QTimer(self)
        timer.singleShot(3000, self.switchToWaiting)
        
    def switchToWaiting(self):
        print 'Timed out'
        waitingWindow.show()
        waitingWindow.checkerThread.start()
        thankyouWindow.hide()

class LogoutWindow(QMainWindow):
    def __init__(self, eventId):
        super(LogoutWindow, self).__init__()
        uic.loadUi('logoutwindow.ui', self)
        self.setWindowFlags(QtCore.Qt.Widget | QtCore.Qt.FramelessWindowHint);
        self.btnBack.clicked.connect(self.back)
        self.btnConfirm.clicked.connect(self.logout)
        self.btnKeyboard.clicked.connect(self.showKeyboard)
        
        self.eventId = eventId
    
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
        
        # TODO: Check if logout credentials are valid
        if self.txtPassword.text() == "":
            msgBox = QMessageBox()
            msgBox.setWindowTitle("Error")
            msgBox.setText("Please enter a password")
            msgBox.addButton(QMessageBox.Ok)
            msgBox.exec_()
            return
        
        self.logoutThread = LogoutThread(self.eventId, self.txtPassword.text())
        self.logoutThread.success.connect(self.success)
        self.logoutThread.start()
    
    def success(self):
        # Switch to login window
        global mainWindow
        global waitingWindow
        mainWindow = MainWindow()
        waitingWindow.checkerThread.terminate()
        mainWindow.show()
        self.hide()

class ThankYouWindow(QMainWindow):
    def __init__(self):
        super(ThankYouWindow, self).__init__()
        uic.loadUi('thankyouwindow.ui', self)
        self.setWindowFlags(QtCore.Qt.Widget | QtCore.Qt.FramelessWindowHint)

app = QApplication(sys.argv)
mainWindow = MainWindow()
waitingWindow = None
logoutWindow = None
votingWindow = None
thankyouWindow = ThankYouWindow()
sys.exit(app.exec_())
