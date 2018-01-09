package com.virk.registrationappandroid.utils;

import android.media.AudioManager;

import com.acs.audiojack.AudioJackReader;

import java.util.Date;

/**
 * Created by ashis on 8/15/2017.
 */

public class Acr3x {
    private Acr3xTransmitter transmitter;
    private AudioManager mAudioManager;
    private AudioJackReader mReader;

    private boolean firstReset = true;  /** Is this the first reset of the reader? */

    /** APDU command for reading a card's UID */
    private final byte[] apdu = { (byte) 0xFF, (byte) 0xCA, (byte) 0x00, (byte) 0x00, (byte) 0x00 };
    /** Timeout for APDU response (in <b>seconds</b>) */
    private final int timeout = 1;
    private int acr3xCardType = AudioJackReader.PICC_CARD_TYPE_ISO14443_TYPE_A
            | AudioJackReader.PICC_CARD_TYPE_ISO14443_TYPE_B
            | AudioJackReader.PICC_CARD_TYPE_FELICA_212KBPS
            | AudioJackReader.PICC_CARD_TYPE_FELICA_424KBPS
            | AudioJackReader.PICC_CARD_TYPE_AUTO_RATS;

    private int acr3xStartAudioLevel = 0;

    private Object locking = new Object();

    private String lastUuid = "";
    private Date lastUuidDate = new Date();

    // Helper function to convert NFC Tag ID to a readable, neat format
    final protected static char[] hexArray = {'0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F'};
    public static String bytesToHex(byte[] bytes) {
        char[] hexChars = new char[bytes.length * 2];
        int v;
        for ( int j = 0; j < bytes.length; j++ ) {
            v = bytes[j] & 0xFF;
            hexChars[j * 2] = hexArray[v >>> 4];
            hexChars[j * 2 + 1] = hexArray[v & 0x0F];
        }
        return "0x" + new String(hexChars);
    }

    public Acr3x(AudioManager mAudioManager){
        this.mAudioManager = mAudioManager;
    }

    public void start(final Acr3xNotifListener listener){
        Runnable r = new Runnable(){

            @Override
            public void run() {
                if(mReader == null){
                    mReader = new AudioJackReader(mAudioManager);
                }
                System.out.println("ACR35 reader start");

                acr3xStartAudioLevel = mAudioManager.getStreamVolume(AudioManager.STREAM_MUSIC);
                System.out.println("acr3x start audio stream level: " + acr3xStartAudioLevel);
                mAudioManager.setStreamVolume(AudioManager.STREAM_MUSIC,
                        mAudioManager.getStreamMaxVolume(AudioManager.STREAM_MUSIC), 0);
                System.out.println("acr3x set audio stream level: " + mAudioManager.getStreamVolume(AudioManager.STREAM_MUSIC));

                mReader.start();
                System.out.println("Started");
                mReader.setSleepTimeout(30);
                mReader.setOnFirmwareVersionAvailableListener(new AudioJackReader.OnFirmwareVersionAvailableListener() {
                    @Override
                    public void onFirmwareVersionAvailable(AudioJackReader reader,
                                                           String firmwareVersion) {
                        System.out.println("acr3x firmware version: " + firmwareVersion);
                        if(listener != null){
                            listener.onFirmwareVersionAvailable(firmwareVersion);
                        }
                        Acr3x.this.read(listener);

                    }
                });
                mReader.reset(new AudioJackReader.OnResetCompleteListener(){

                    @Override
                    public void onResetComplete(AudioJackReader arg0) {
                        System.out.println("Reset complete");
                        read(listener);
                        mReader.getFirmwareVersion();

                    }

                });
            }
        };

        Thread t = new Thread(r, "Acr3xInitThread");
        t.start();

    }

    public void read(final Acr3xNotifListener callbackContext){
        System.out.println("acr3x setting up for reading...");
        firstReset = true;

        /* Set the PICC response APDU callback */
        mReader.setOnPiccResponseApduAvailableListener
                (new AudioJackReader.OnPiccResponseApduAvailableListener() {
                    @Override
                    public void onPiccResponseApduAvailable(AudioJackReader reader,
                                                            byte[] responseApdu) {
                        /* Update the connection status of the transmitter */
                        transmitter.updateStatus(true);

                        /* Print out the UID */
                        String uuid = bytesToHex(responseApdu);

                        if(uuid.equalsIgnoreCase("0x9000")){
                            return;
                        }

                        if(uuid.endsWith("9000")){
                            uuid = uuid.substring(0, uuid.length() - 4);
                        }

                        if(uuid.equals(lastUuid)){ // na odfiltrovanie opakujucich sa uuid z citacky z predchadzajuceho citania
                            if(new Date().getTime() - lastUuidDate.getTime() < 3000){
                                return;
                            }
                        }

                        lastUuid = uuid;
                        lastUuidDate = new Date();

                        synchronized(locking){

                            System.out.println("acr3x uuid: " + uuid);

                            if(callbackContext != null){
                                callbackContext.onUUIDAavailable(uuid);
                            }
                            System.out.println("acr3x restarting reader");
                            transmitter.kill();
                            try {
                                locking.wait(2000);
                            } catch (InterruptedException e) {
                                // TODO Auto-generated catch block
                                e.printStackTrace();
                            }
                        }

                        read(callbackContext);



                    }
                });

        /* Set the reset complete callback */
        mReader.setOnResetCompleteListener(new AudioJackReader.OnResetCompleteListener() {
            @Override
            public void onResetComplete(AudioJackReader reader) {
                System.out.println("acr3x reset complete");

                /* If this is the first reset, the ACR35 reader must be turned off and back on again
                   to work reliably... */
                Thread t = null;
                if(firstReset){  //firstReset
                    t = new Thread(new Runnable() {
                        public void run() {
                            try{
                                /* Set the reader asleep */
                                mReader.sleep();
                                /* Wait one second */
                                Thread.sleep(500);
                                /* Reset the reader */
                                mReader.reset();

                                firstReset = false;
                            } catch (InterruptedException e) {
                                e.printStackTrace();
                                // TODO: add exception handling
                            }
                        }
                    });

                } else {
                    /* Create a new transmitter for the UID read command */
                    transmitter = new Acr3xTransmitter(mReader, mAudioManager, timeout,
                            apdu, acr3xCardType, locking);
                    t = new Thread(transmitter);
                }
                t.start();
            }
        });

        mReader.start();
        mReader.reset();
        System.out.println("acr3x setup complete");
    }

    public void stop(){
        if(transmitter != null){
            transmitter.kill();
        }

        System.out.println("acr3x restoring audio level: " + acr3xStartAudioLevel);
        mAudioManager.setStreamVolume(AudioManager.STREAM_MUSIC,acr3xStartAudioLevel, 0);
        System.out.println("acr3x set audio stream level: " + mAudioManager.getStreamVolume(AudioManager.STREAM_MUSIC));

        if(mReader != null){
            mReader.stop();
        }
    }
}
