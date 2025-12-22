const { app, BrowserWindow, Menu, ipcMain } = require('electron');

const createWindow = () => {
    // Default create a 1080p window
    const win = new BrowserWindow({
        width: 1920,
        height: 1080,
        autoHideMenuBar: true,
        backgroundColor: '#151515',
        frame: false,
        titleBarStyle: 'hiddenInset',
        webPreferences: {
            preload: __dirname + '/aeonshift-mtg-web-calculator-app-preload.js'
        }
    });

    // Remove default menu
    Menu.setApplicationMenu(null);

    // Handle window control events (reduce/maximize/close) from renderer process
    ipcMain.on('win:minimize', (event) => {
        const window = BrowserWindow.fromWebContents(event.sender);
        window.minimize();
    });

    ipcMain.on('win:maximize', (event) => {
        const window = BrowserWindow.fromWebContents(event.sender);
        if (window.isMaximized()) {
            window.restore();
            event.sender.send('win:isMaximized', false);
        } else {
            window.maximize();
            event.sender.send('win:isMaximized', true);
        }
    });

    ipcMain.on('win:close', (event) => {
        const window = BrowserWindow.fromWebContents(event.sender);
        window.close();
    });

    win
        .loadFile('public/static-calculators/mtg/aeonshift-mtg-calculator.html')
        .then(r => {
            return 1;
        });

    // Open the DevTools locally for debugging, except in production
    if (!app.isPackaged) {
        win.webContents.openDevTools({ mode: 'detach' });
    }
};

// This method will be called when Electron has finished
app
    .whenReady()
    .then(() => {
        createWindow();

        // Open a window if none are open (MacOS)
        app.on('activate', () => {
            if (BrowserWindow.getAllWindows().length === 0) {
                createWindow();
            }
        });
    });

// Quit the app when all windows are closed (Windows & Linux)
app
    .on('window-all-closed', () => {
        if (process.platform !== 'darwin') {
            app.quit();
        }
    });
