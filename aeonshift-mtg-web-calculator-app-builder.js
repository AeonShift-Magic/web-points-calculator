const { app, BrowserWindow } = require('electron')

const createWindow = () => {
    const win = new BrowserWindow({
        width: 1920, height: 1080
    })

    win.loadFile('public/static-calculators/mtg/aeonshift-mtg-calculator.html').then(r => {
        return 1;
    });
}

// This method will be called when Electron has finished
app.whenReady().then(() => {
    createWindow();

    // Open a window if none are open (MacOS)
    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    })
})

// Quit the app when all windows are closed (Windows & Linux)
app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
})
