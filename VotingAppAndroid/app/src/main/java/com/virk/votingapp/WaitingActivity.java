package com.virk.votingapp;

import android.Manifest;
import android.app.PendingIntent;
import android.app.ProgressDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.pm.PackageManager;
import android.media.AudioManager;
import android.nfc.NfcAdapter;
import android.nfc.Tag;
import android.nfc.tech.IsoDep;
import android.nfc.tech.NfcB;
import android.support.v4.app.ActivityCompat;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.Log;
import android.view.Gravity;
import android.view.View;
import android.view.WindowManager;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.virk.votingapp.utils.Acr3x;
import com.virk.votingapp.utils.Acr3xNotifListener;
import com.virk.votingapp.utils.Utils;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

/*
 * The WaitingActivity class presents a message instructing voters
 * to vote for the group. It also enables NFC and waits for a tag to
 * be tapped on the phone
 *
 * @author  Isaac Ashwin
 * @version 1.0
 * @since   2017-6-20
 */

public class WaitingActivity extends AppCompatActivity {
    private static final String MIME_TEXT_PLAIN = "text/plain"; // MIME type for NFC tag
    private static String TAG = "WaitingActivity"; // Tag for logging

    private static final int PERMISSIONS_REQUEST_RECORD_AUDIO = 0;
    private static final int VOTING_INTENT = 1;

    Acr3x acr3x;
    Acr3xNotifListener listener;

    // Event and group details
    private long event, group;
    private String groupName;

    // Reference to global application object
    private MainApplication application;

    // Object representing NFC hardware
    private NfcAdapter mNfcAdapter;

    // Handle to UI elements
    private TextView txtGroupName;
    private Button btnLogout;

    // Boolean to check if the current activity is obscured
    boolean activityCovered = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        // Prevent screen from entering standby
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);

        // Load previous state and layout xml
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_waiting);

        // Get data sent from previous activity (MainActivity)
        event = getIntent().getLongExtra("eventid", 0);
        group = getIntent().getLongExtra("groupid", 0);
        groupName = getIntent().getStringExtra("groupname");

        // Get handle to global application object
        application = (MainApplication) getApplicationContext();

        // Get handles to UI elements
        txtGroupName = (TextView) findViewById(R.id.txtGroupName);
        btnLogout = (Button) findViewById(R.id.btnLogout);

        // Set group name
        txtGroupName.setText(groupName);

        // Listen for logout button click
        btnLogout.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Display a dialog to allow users to enter logout password
                AlertDialog.Builder logoutBuilder = new AlertDialog.Builder(WaitingActivity.this);
                logoutBuilder.setTitle("Logout of VIRK");
                logoutBuilder.setMessage("Please enter the logout password");

                LinearLayout layout = new LinearLayout(WaitingActivity.this);
                layout.setOrientation(LinearLayout.VERTICAL);
                layout.setGravity(Gravity.CENTER_HORIZONTAL);
                layout.setPadding(Utils.dpToPx(WaitingActivity.this, 24), 0, Utils.dpToPx(WaitingActivity.this, 24), 0);
                final EditText input = new EditText(WaitingActivity.this);
                layout.addView(input);

                logoutBuilder.setView(layout);
                logoutBuilder.setCancelable(true);

                // Add listeners for each button in the dialog
                logoutBuilder.setPositiveButton("Ok", new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        // Create object with data for POST request
                        Map<String, String> logoutObject = new HashMap<>();
                        logoutObject.put("eventid", event + "");
                        logoutObject.put("password", input.getText().toString());

                        if(input.getText().toString().isEmpty()) {
                            Toast.makeText(WaitingActivity.this, "Please enter the logout password", Toast.LENGTH_LONG).show();
                            return;
                        }

                        // Create POST request to logout of app
                        CustomRequest logoutRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/LogoutApp.php", logoutObject, new Response.Listener<JSONObject>() {
                            @Override
                            public void onResponse(JSONObject response) {
                                try {
                                    // Check for successful response
                                    if(response.getBoolean("success")) {
                                        // Launch MainActivity again and close this activity
                                        Intent logoutIntent = new Intent(WaitingActivity.this, MainActivity.class);
                                        startActivity(logoutIntent);
                                        if(acr3x != null) {
                                            acr3x.stop();
                                        }
                                        finish();
                                    }
                                    else {
                                        // Display error message to users for rectification
                                        Toast.makeText(WaitingActivity.this, response.getString("message"), Toast.LENGTH_SHORT).show();
                                    }
                                } catch (JSONException e) {
                                    e.printStackTrace();
                                }
                            }
                        }, new Response.ErrorListener() {
                            @Override
                            public void onErrorResponse(VolleyError error) {
                                error.printStackTrace();
                            }
                        });

                        // Launch request
                        application.queue.add(logoutRequest);
                    }
                });
                logoutBuilder.setNegativeButton("Cancel", new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        // Close dialog if cancel is pressed
                        dialog.cancel();
                    }
                });
                // Display dialog
                logoutBuilder.show();
            }
        });

        // Get a handle to the NFC adapter
        mNfcAdapter = NfcAdapter.getDefaultAdapter(this);

        if (mNfcAdapter == null) {
            Log.d(TAG, "Failed to find NFC adapter. Falling back to ACR35");
            // Fall back to ACR35
            AudioManager manager = (AudioManager)getSystemService(Context.AUDIO_SERVICE);
            if(!manager.isWiredHeadsetOn()) {
                Toast.makeText(WaitingActivity.this, "Please connect the ACR35 NFC Reader and restart the app", Toast.LENGTH_LONG).show();
                finish();
                return;
            }
            testACR();
            return;
        }

        if (!mNfcAdapter.isEnabled()) {
            // Notify user that NFC is disabled
           Toast.makeText(this, "NFC is disabled.", Toast.LENGTH_SHORT).show();
        }
    }

    @Override
    protected void onResume() {
        super.onResume();

        // Make current application receive NFC requests
        setupForegroundDispatch(mNfcAdapter);
    }

    @Override
    protected void onPause() {
        if(mNfcAdapter != null) {
            // Allow other applications to receive NFC requests again
            stopForegroundDispatch(mNfcAdapter);
        }

        super.onPause();
    }

    @Override
    protected void onDestroy() {
        if(acr3x != null) {
            acr3x.stop();
        }
        super.onDestroy();
    }

    @Override
    public void onBackPressed() {
        // Prevent back button from closing application
    }

    @Override
    protected void onNewIntent(Intent intent) {
        String action = intent.getAction();

        // Check to make sure NFC tag is of the right type
        if (NfcAdapter.ACTION_TECH_DISCOVERED.equals(action)) {
            // Read ID from tag
            Tag tag = intent.getParcelableExtra(NfcAdapter.EXTRA_TAG);
            String tagId = bytesToHex(tag.getId());

            // Authenticate the ID to make sure it is a valid ID
            authenticateTagId(tagId);
        }
        else {
            finish();
        }
    }

    /* authenticateTagId(String tagId): This method takes a tag ID
     * and submits it to the server to authenticate it. It prevents
     * double voting and self voting.
     */
    private void authenticateTagId(String tagId) {
        // Display a loading dialog
        final ProgressDialog dialog = new ProgressDialog(WaitingActivity.this);
        dialog.setTitle("Authenticating Tag");
        dialog.setMessage("Checking if this tag is valid");
        dialog.setIndeterminate(true);
        dialog.show();

        // Create object with data for POST request
        Map<String, String> authenticateObject = new HashMap<>();
        authenticateObject.put("tagId", tagId);
        authenticateObject.put("eventid", event + "");
        authenticateObject.put("groupid", group + "");

        // Create POST request to authenticate tag
        CustomRequest authenticateRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/AuthenticateTagId.php", authenticateObject, new Response.Listener<JSONObject>() {
            @Override
            public void onResponse(JSONObject response) {
                try {
                    // Remove loading dialog
                    dialog.cancel();
                    Log.d(TAG, response.toString());
                    // Check for successful response from server
                    if(response.getBoolean("success")) {
                        // Launch VotingActivity to allow authenticated user to vote
                        Intent votingIntent = new Intent(WaitingActivity.this, VotingActivity.class);

                        // Send student data to voting activity
                        votingIntent.putExtra("eventid", event);
                        votingIntent.putExtra("groupid", group);
                        votingIntent.putExtra("groupname", groupName);
                        votingIntent.putExtra("studentid", response.getString("studentid"));

                        startActivityForResult(votingIntent, VOTING_INTENT);

                        activityCovered = true;
                    }
                    else {
                        // Display error message to user for rectification
                        Toast.makeText(WaitingActivity.this, response.getString("message"), Toast.LENGTH_LONG).show();
                        if(acr3x != null){
                            acr3x.read(listener);
                        }
                    }
                } catch (JSONException e) {
                    e.printStackTrace();
                }
            }
        }, new Response.ErrorListener() {
            @Override
            public void onErrorResponse(VolleyError error) {
                error.printStackTrace();
            }
        });

        // Launch request
        application.queue.add(authenticateRequest);
    }

    // Helper function to convert NFC Tag ID to a readable, neat format
    final protected static char[] hexArray = {'0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F'};
    public static String bytesToHex(byte[] bytes) {
        char[] hexChars = new char[bytes.length * 3 - 1];
        int v;
        for ( int j = 0; j < bytes.length; j++ ) {
            v = bytes[j] & 0xFF;
            hexChars[j * 3] = hexArray[v >>> 4];
            hexChars[j * 3 + 1] = hexArray[v & 0x0F];
            if(j != bytes.length - 1) {
                hexChars[j * 3 + 2] = ':';
            }
        }
        return new String(hexChars);
    }

    /* setupForegroundDispatch(NfcAdapter adapter): This method
     * sets up the application to receive all NFC requests with
     * the highest priority.
     */
    public void setupForegroundDispatch(NfcAdapter adapter) {
        if(adapter == null) {
            return;
        }
        // Create an intent to this activity and ensure it is the only activity created
        final Intent intent = new Intent(this, getClass());
        intent.setFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP);

        // Create a pending intent from intent
        final PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, intent, 0);

        // Set intent filters to receiving NFC requests
        IntentFilter[] filters = new IntentFilter[1];
        String[][] techList = new String[][]{ new String[] {NfcB.class.getName(), IsoDep.class.getName()}};

        // Notice that this is the same filter as in our manifest.
        filters[0] = new IntentFilter();
        filters[0].addAction(NfcAdapter.ACTION_TECH_DISCOVERED);
        filters[0].addCategory(Intent.CATEGORY_DEFAULT);
        try {
            filters[0].addDataType(MIME_TEXT_PLAIN);
        } catch (IntentFilter.MalformedMimeTypeException e) {
            throw new RuntimeException("Check your mime type.");
        }

        // Set up NFC hardware to call this intent if tag is discovered
        adapter.enableForegroundDispatch(this, pendingIntent, filters, techList);
    }

    public void stopForegroundDispatch(NfcAdapter adapter) {
        if(adapter == null) {
            return;
        }
        // Remove application from foreground dispatch
        adapter.disableForegroundDispatch(this);
    }

    public void testACR() {
        // Assume thisActivity is the current activity
        int permissionCheck = ContextCompat.checkSelfPermission(this, Manifest.permission.RECORD_AUDIO);
        if(permissionCheck !=  PackageManager.PERMISSION_GRANTED) {
            Log.d(TAG, "Permission missing");
            ActivityCompat.requestPermissions(this,
                    new String[]{Manifest.permission.RECORD_AUDIO},
                    PERMISSIONS_REQUEST_RECORD_AUDIO);
        }
        else {
            Log.d(TAG, "Permission available");
            AudioManager manager = (AudioManager) getSystemService(Context.AUDIO_SERVICE);
            acr3x = new Acr3x(manager);
            listener = new Acr3xNotifListener() {
                @Override
                public void onUUIDAavailable(String uuid) {
                    if (!uuid.equals("0x6300") && !activityCovered) {
                        String baseUUID = uuid.substring(2, uuid.length());
                        final StringBuilder sb = new StringBuilder();
                        for (int i = 0; i < baseUUID.length(); i++) {
                            sb.append(baseUUID.charAt(i));
                            if ((i + 1) % 2 == 0 && i != baseUUID.length() - 1) {
                                sb.append(":");
                            }
                        }
                        Log.d(TAG, sb.toString());
                        runOnUiThread(new Runnable() {
                            @Override
                            public void run() {
                                authenticateTagId(sb.toString());
                            }
                        });
                    }
                }

                @Override
                public void onFirmwareVersionAvailable(String firmwareVersion) {
                    Log.d(TAG, firmwareVersion);
                }
            };

            acr3x.start(listener);
            Log.d(TAG, "STARTED");
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode,
                                           String permissions[], int[] grantResults) {
        switch (requestCode) {
            case PERMISSIONS_REQUEST_RECORD_AUDIO: {
                // If request is cancelled, the result arrays are empty.
                if (grantResults.length > 0
                        && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                    testACR();
                    // permission was granted, yay! Do the
                    // contacts-related task you need to do.

                } else {
                    Toast.makeText(this, "Audio recording permission required", Toast.LENGTH_LONG).show();
                }
                return;
            }
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        if(requestCode == VOTING_INTENT) {
            activityCovered = false;
            //acr3x.read(listener);
        }
    }
}
