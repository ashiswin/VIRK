package com.virk.redemptionappandroid;

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
import android.provider.Settings;
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
import com.android.volley.toolbox.StringRequest;
import com.virk.redemptionappandroid.utils.Acr3x;
import com.virk.redemptionappandroid.utils.Acr3xNotifListener;
import com.virk.redemptionappandroid.utils.Utils;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

import static android.provider.Settings.ACTION_MANAGE_WRITE_SETTINGS;

public class WaitingActivity extends AppCompatActivity {
    private static final String MIME_TEXT_PLAIN = "text/plain";
    private static String TAG = "WaitingActivity";

    // Constants
    private static final int PERMISSIONS_REQUEST_RECORD_AUDIO = 0;
    private static final int REDEMPTION_INTENT = 1;

    // Handles to external ACR35 NFC Reader
    Acr3x acr3x;
    Acr3xNotifListener listener;

    private long event;
    private String reward;
    private MainApplication application;
    private NfcAdapter mNfcAdapter;

    // Handles to UI
    private TextView txtReward;
    private Button btnLogout;

    // Variable to check if activity is being occluded
    boolean activityCovered = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);

        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_waiting);

        event = getIntent().getLongExtra("eventid", 0);
        application = (MainApplication) getApplicationContext();

        txtReward = (TextView) findViewById(R.id.txtReward);
        btnLogout = (Button) findViewById(R.id.btnLogout);

        StringRequest registerRequest = new StringRequest(Request.Method.GET, MainApplication.SERVER_HOST + "/scripts/GetEventOnly.php?eventid=" + event, new Response.Listener<String>() {
            @Override
            public void onResponse(String r) {
                try {
                    JSONObject response = new JSONObject(r);
                    if(response.getBoolean("success")) {
                        reward = response.getJSONObject("event").getString("reward");
                        txtReward.setText(reward);
                    }
                    else {
                        Log.d(TAG, response.getString("message"));
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

        application.queue.add(registerRequest);
        btnLogout.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                AlertDialog.Builder logoutBuilder = new AlertDialog.Builder(WaitingActivity.this);
                logoutBuilder.setTitle("Logout of VIRK Redeem");
                logoutBuilder.setMessage("Please enter the logout password");

                LinearLayout layout = new LinearLayout(WaitingActivity.this);
                layout.setOrientation(LinearLayout.VERTICAL);
                layout.setGravity(Gravity.CENTER_HORIZONTAL);
                layout.setPadding(Utils.dpToPx(WaitingActivity.this, 24), 0, Utils.dpToPx(WaitingActivity.this, 24), 0);
                final EditText input = new EditText(WaitingActivity.this);
                layout.addView(input);

                logoutBuilder.setView(layout);
                logoutBuilder.setCancelable(true);
                logoutBuilder.setPositiveButton("Ok", new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        Map<String, String> logoutObject = new HashMap<>();
                        logoutObject.put("eventid", event + "");
                        logoutObject.put("password", input.getText().toString());

                        if(input.getText().toString().isEmpty()) {
                            Toast.makeText(WaitingActivity.this, "Please enter the logout password", Toast.LENGTH_LONG).show();
                            return;
                        }

                        CustomRequest logoutRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/LogoutApp.php", logoutObject, new Response.Listener<JSONObject>() {
                            @Override
                            public void onResponse(JSONObject response) {
                                try {
                                    if(response.getBoolean("success")) {
                                        Intent logoutIntent = new Intent(WaitingActivity.this, MainActivity.class);
                                        startActivity(logoutIntent);
                                        if(acr3x != null) {
                                            acr3x.stop();
                                        }
                                        finish();
                                    }
                                    else {
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

                        application.queue.add(logoutRequest);
                    }
                });
                logoutBuilder.setNegativeButton("Cancel", new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        dialog.cancel();
                    }
                });
                logoutBuilder.show();
            }
        });

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
            Toast.makeText(this, "NFC is disabled.", Toast.LENGTH_SHORT).show();
        }
    }

    @Override
    protected void onResume() {
        super.onResume();

        setupForegroundDispatch(mNfcAdapter);
    }

    @Override
    protected void onPause() {
        stopForegroundDispatch(mNfcAdapter);

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
    }

    @Override
    protected void onNewIntent(Intent intent) {
        String action = intent.getAction();

        if (NfcAdapter.ACTION_TECH_DISCOVERED.equals(action)) {
            Tag tag = intent.getParcelableExtra(NfcAdapter.EXTRA_TAG);
            String tagId = bytesToHex(tag.getId());

            authenticateTagId(tagId);
        }
        else {
            finish();
        }
    }

    private void authenticateTagId(String tagId) {
        // Display a loading dialog
        final ProgressDialog dialog = new ProgressDialog(WaitingActivity.this);
        dialog.setTitle("Authenticating Tag");
        dialog.setMessage("Checking if this tag is valid");
        dialog.setIndeterminate(true);
        dialog.show();

        Map<String, String> redeemObject = new HashMap<>();
        redeemObject.put("tagId", tagId);
        redeemObject.put("eventid", event + "");
        CustomRequest authenticateRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/RedeemTagId.php", redeemObject, new Response.Listener<JSONObject>() {
            @Override
            public void onResponse(JSONObject response) {
                try {
                    // Remove loading dialog
                    dialog.cancel();

                    if(response.getBoolean("success")) {
                        Intent redemptionIntent = new Intent(WaitingActivity.this, RedemptionActivity.class);
                        redemptionIntent.putExtra("eventid", event);
                        redemptionIntent.putExtra("studentid", response.getString("studentid"));
                        redemptionIntent.putExtra("reward", reward);
                        startActivityForResult(redemptionIntent, REDEMPTION_INTENT);

                        activityCovered = true;
                    }
                    else {
                        Toast.makeText(WaitingActivity.this, response.getString("message"), Toast.LENGTH_LONG).show();
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

        application.queue.add(authenticateRequest);
    }

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

    public void setupForegroundDispatch(NfcAdapter adapter) {
        if(adapter == null) {
            return;
        }

        final Intent intent = new Intent(this, getClass());
        intent.setFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP);

        final PendingIntent pendingIntent = PendingIntent.getActivity(getApplicationContext(), 0, intent, 0);

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

        adapter.enableForegroundDispatch(this, pendingIntent, filters, techList);
    }

    public void stopForegroundDispatch(NfcAdapter adapter) {
        if(adapter == null) {
            return;
        }

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
        if(requestCode == REDEMPTION_INTENT) {
            activityCovered = false;
            //acr3x.read(listener);
        }
    }
}
