package com.virk.votingapp;

import android.app.ProgressDialog;
import android.content.Intent;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ImageButton;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

public class VotingActivity extends AppCompatActivity {
    private static String TAG = "VotingActivity";
    private MainApplication application;

    private TextView txtGroupName;
    private Button btnVote;
    private ImageButton[] stars = new ImageButton[3];

    private long event, group;
    private String groupName, studentId;

    private int score;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_voting);

        application = (MainApplication) getApplicationContext();

        event = getIntent().getLongExtra("eventid", 0);
        group = getIntent().getLongExtra("groupid", 0);
        groupName = getIntent().getStringExtra("groupname");
        studentId = getIntent().getStringExtra("studentid");

        txtGroupName = (TextView) findViewById(R.id.txtGroupName);
        btnVote = (Button) findViewById(R.id.btnVote);
        stars[0] = (ImageButton) findViewById(R.id.btn1);
        stars[1] = (ImageButton) findViewById(R.id.btn2);
        stars[2] = (ImageButton) findViewById(R.id.btn3);

        txtGroupName.setText(groupName);

        for(int i = 0; i < 3; i++) {
            stars[i].setOnClickListener(new StarClickListener(i));
        }

        btnVote.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                if(score == 0) {
                    Toast.makeText(VotingActivity.this, "Please select a voting score", Toast.LENGTH_SHORT).show();
                    return;
                }

                // Display loading screen
                final ProgressDialog dialog = new ProgressDialog(VotingActivity.this);
                dialog.setTitle("Submitting Vote");
                dialog.setMessage("Please wait while your vote is submitted");
                dialog.setIndeterminate(true);
                dialog.show();

                Map<String, String> voteObject = new HashMap<>();
                voteObject.put("eventid", event + "");
                voteObject.put("groupid", group + "");
                voteObject.put("studentid", studentId);
                voteObject.put("score", score + "");

                CustomRequest voteRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/Vote.php", voteObject, new Response.Listener<JSONObject>() {
                    @Override
                    public void onResponse(JSONObject response) {
                        // Remove loading dialog
                        dialog.cancel();

                        try {
                            if(response.getBoolean("success")) {
                                Intent thankYouIntent = new Intent(VotingActivity.this, ThankYouActivity.class);
                                thankYouIntent.putExtra("votes", response.getInt("votes"));
                                startActivity(thankYouIntent);
                                finish();
                            }
                            else {
                                Toast.makeText(VotingActivity.this, response.getString("message"), Toast.LENGTH_LONG).show();
                                if(response.getBoolean("close")) {
                                    finish();
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

                application.queue.add(voteRequest);
            }
        });
    }

    private class StarClickListener implements View.OnClickListener {
        int position;
        StarClickListener(int position) {
            this.position = position;
        }
        @Override
        public void onClick(View v) {
            score = position + 1;
            for(int i = 0; i <= position; i++) {
                stars[i].setImageDrawable(getResources().getDrawable(R.drawable.star_active));
            }
            for(int i = position + 1; i < stars.length; i++) {
                stars[i].setImageDrawable(getResources().getDrawable(R.drawable.star_normal));
            }
        }
    }
}
