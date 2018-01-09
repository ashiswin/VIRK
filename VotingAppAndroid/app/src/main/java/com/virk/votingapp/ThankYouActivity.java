package com.virk.votingapp;

import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.widget.TextView;

import java.util.Timer;
import java.util.TimerTask;

public class ThankYouActivity extends AppCompatActivity {
    private static String TAG = "ThankYouActivity";

    private MainApplication application;

    private int votes, minVotes;
    private String reward;

    private TextView txtIntro, txtReward, txtCountdown;

    private int countdown = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_thank_you);

        votes = getIntent().getIntExtra("votes", -1);
        application = (MainApplication) getApplicationContext();

        minVotes = application.minVotes;
        reward = application.reward;

        txtIntro = (TextView) findViewById(R.id.txtIntro);
        txtReward = (TextView) findViewById(R.id.txtReward);
        txtCountdown = (TextView) findViewById(R.id.txtCountdown);

        if(votes < minVotes) {
            txtIntro.setText(getResources().getQuantityString(R.plurals.thankyou_intro, minVotes - votes, minVotes - votes));
        }
        else {
            txtIntro.setText(getString(R.string.thankyou_intro_complete));
        }

        txtReward.setText(reward.toUpperCase());

        Timer closeTimer = new Timer();
        closeTimer.scheduleAtFixedRate(new TimerTask() {
            @Override
            public void run() {
               runOnUiThread(new Runnable() {
                   @Override
                   public void run() {
                       txtCountdown.setText("Returning to previous page in " + (5 - countdown));
                       countdown++;
                       if(countdown == 5) {
                           finish();
                       }
                   }
               });
            }
        }, 0, 1000);
    }
}
