package io.jiaozi;

import android.content.Context;
import android.content.SharedPreferences;
import android.os.Build;
import android.support.annotation.NonNull;
import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.*;
import java.util.concurrent.TimeUnit;

public class Jiaozi {

    private Jiaozi() {
    }

    private static final String K_APPLICATION = "JIAOZI_SDK";
    private static Context context;
    private static String domain = null;
    private static String current_exp_id = null;
    private static Map<String, String> _config = new HashMap<>();
    private static boolean started = false;
    private static ArrayList<String> _history = new ArrayList<>();
    private static boolean debugNetwork = false;
    static Integer timeOffset = null;

    /**
     * Init Tracker
     *
     * @param context application context
     * @param domain self-host jiaozi domain
     */
    public static void init(@NonNull Context context, @NonNull String domain) {
        Jiaozi.init(context, domain, null);
    }

    /**
     * Init Tracker with current context
     *
     * @param context application context
     */
    public static void init(@NonNull Context context, @NonNull String domain, Boolean debugNetwork) {
        Jiaozi.context = context.getApplicationContext();

        if (debugNetwork != null) {
            Jiaozi.debugNetwork = debugNetwork;
        }
        Jiaozi.domain = domain;
        if (!started) {
            started = true;
            new Timer().schedule(new TimerTask() {
                @Override
                public void run() {
                    track("general", "start");
                }
            }, 5_000);
        }
    }

    /**
     * Set tracker profile_id.
     * Please contact Jiaozi provider for this id
     *
     * @param profile_id jiaozi profile id
     */
    public static void config(String profile_id) {
        String uuid = getUUID();
        Jiaozi.config(profile_id, uuid);
    }

    /**
     * Set tracker profile id.
     * And manually define device unique id
     *
     * @param profile_id get profile_id from admin
     * @param uuid device id
     */
    public static void config(String profile_id, String uuid) {
        saveConfig("profile_id", profile_id);
        saveConfig("uuid", uuid);
    }

    /**
     * If user already login.
     * Should pass an encrypted user_id
     *
     * @param userId app user id if user login
     */
    public static void setUserId(String userId) {
        Jiaozi.saveConfig("user_id", userId);
    }

    public static void removeUserId() {
        Jiaozi.removeConfig("user_id");
    }

    /*
     * Manual Event Track Method
     */

    public static void track(String category, String action) {
        try {
            trackEvent(category, action, null, null);
        } catch (Exception ex) {
            _errorDebug(ex.toString());
        }
    }

    public static void track(String category, String action, String label) {
        try {
            trackEvent(category, action, label, null);
        } catch (Exception ex) {
            _errorDebug(ex.toString());
        }
    }

    public static void track(String category, String action, String label, String value) {
        try {
            Map<String, String> extra_value = new HashMap<String, String>();
            extra_value.put("value", value);
            trackEvent(category, action, label, extra_value);
        } catch (Exception ex) {
            _errorDebug(ex.toString());
        }
    }

    public static void track(String category, String action, String label, double value) {
        try {
            track(category, action, label, value, null);
        } catch (Exception ex) {
            _errorDebug(ex.toString());
        }
    }

    /**
     * Track User behavior with this parameters
     *
     * @param category  event category
     * @param action    event action
     * @param label     event label
     * @param value     event number value
     * @param extra    if you have much more event info to send.use this parameter
     */
    public static void track(String category, String action, String label, double value, Map<String, String> extra) {
        try {
            extra.put("value_number", Double.toString(value));
            trackEvent(category, action, label, extra);
        } catch (Exception ex) {
            _errorDebug(ex.toString());
        }
    }

    /**
     * Get Variation when experiment is running
     *
     * @param experiment_id get current experiment id from admin
     * @param callback return variation by this callback
     */
    public static void getVariation(final String experiment_id, final Callback callback) {
        Jiaozi.current_exp_id = experiment_id;
        request("experiments/" + getConfig("profile_id") + ".json", null, new Callback() {
            @Override
            public void onResult(boolean success, Object data) {
                //request error
                if (!success) {
                    callback.onResult(false, null);
                    return;
                }

                try {
                    //search for match experiment
                    JSONObject experiment = null;
                    JSONArray experiments = new JSONArray((String) data);
                    for (int i = 0; i < experiments.length(); i++) {
                        experiment = experiments.getJSONObject(i);
                        String exp_id = experiment.getString("experiment_id");
                        if (!exp_id.equals(experiment_id)) {
                            experiment = null;
                            continue;
                        }
                        JSONObject filter = experiment.getJSONObject("filter");

                        if (filter.has("os") && !"Android".equalsIgnoreCase(filter.getString("os"))) {
                            experiment = null;
                            break;
                        }
                        if (filter.has("client_version")
                                && !getAppVersion().equalsIgnoreCase(filter.getString("client_version"))) {
                            experiment = null;
                            break;
                        }
                        break;
                    }

                    String variation_key = getVariationKey(experiment_id);
                    //do not match current experiment
                    if (experiment == null) {
                        removeConfig(variation_key);
                        callback.onResult(true, null);
                        return;
                    }

                    //check if already assign variation
                    String savedVariation = getConfig(variation_key);
                    if (savedVariation != null && !savedVariation.isEmpty()) {
                        try {
                            int savedVariationInt = Integer.parseInt(savedVariation);
                            callback.onResult(true, savedVariationInt);
                        } catch (Exception ex) {
                            callback.onResult(false, null);
                        }
                        return;
                    }

                    //random if in experiment traffic
                    double traffic_in_exp = experiment.getDouble("traffic_in_exp");
                    double guess = Math.random();
                    if (guess > traffic_in_exp) {
                        saveConfig(variation_key, "-1");
                        callback.onResult(true, -1);
                        return;
                    }

                    //if in experiment,assign variation
                    JSONArray variations = experiment.getJSONArray("variations");
                    guess = Math.random();
                    double weight = 0;
                    for (int i = 0; i < variations.length(); i++) {
                        JSONObject variation = variations.getJSONObject(i);
                        weight += variation.getDouble("weight");
                        if (guess < weight) {
                            saveConfig(variation_key, variation.getString("index"));
                            callback.onResult(true, variation.getInt("index"));
                            break;
                        }
                    }

                } catch (Exception ex) {
                    Log.e(K_APPLICATION, ex.getMessage(), ex);
                    callback.onResult(false, null);
                }

            }
        });
    }

    private static void trackEvent(String category, String action, String label, Map<String, String> value) {
        try {
            flushHistory(action, label);
            Map<String, String> params = new HashMap<>();
            params.put("category", category);
            params.put("action", action);
            if (label != null && !label.isEmpty()) {
                params.put("label", label);
            }
            if (value != null) {
                if (value.containsKey("value")) {
                    params.put("value", value.get("value"));
                    value.remove("value");
                }
                if (value.containsKey("value_number")) {
                    params.put("value_number", value.get("value_number"));
                    value.remove("value_number");
                }
                JSONObject json = new JSONObject(value);
                params.put("value", json.toString());
            }
            params.put("type", "event");
            request("collect_img.gif", params, null);
        } catch (Exception ex) {
            _errorDebug(ex.toString());
        }
    }

    /**
     * Send Debug History
     *
     * @param action    event action
     * @param label     event label
     */
    private static synchronized void flushHistory(String action, String label) {
        //debugNetwork is disabled
        if (!Jiaozi.debugNetwork) {
            return;
        }
        if ("history".equals(action)) {
            return;
        }
        _history.add(action + ":" + label);
        if (_history.size() > 10) {
            StringBuilder sb = new StringBuilder();
            for (String h : _history) {
                sb.append(h).append(",");
            }
            _history.clear();
            track("debug", "history", sb.toString());
        }
    }

    /**
     * Get previous Device UUID or generate new if empty
     */
    private static String getUUID() {
        String uuid = getConfig("uuid");
        if (uuid == null) {
            uuid = UUID.randomUUID().toString();
        }
        return uuid;
    }

    private static void saveConfig(String key, String value) {
        if (value == null || value.isEmpty()) {
            return;
        }
        SharedPreferences sp = context.getSharedPreferences(K_APPLICATION, Context.MODE_PRIVATE);
        SharedPreferences.Editor editor = sp.edit();
        editor.putString(key, value);
        editor.apply();
        _config.put(key, value);
    }

    protected static String getConfig(String key) {
        if (_config.containsKey(key)) {
            return _config.get(key);
        }
        SharedPreferences sp = context.getSharedPreferences(K_APPLICATION, Context.MODE_PRIVATE);
        String value = sp.getString(key, null);
        if (value != null) {
            _config.put(key, value);
        }
        return value;
    }

    private static void removeConfig(String key) {
        _config.remove(key);
        SharedPreferences sp = context.getSharedPreferences(K_APPLICATION, Context.MODE_PRIVATE);
        SharedPreferences.Editor editor = sp.edit();
        editor.remove(key);
        editor.apply();
    }


    private static OkHttpClient okhttpClient = null;

    /**
     * Common request method
     *
     * @param path     url path after domain
     * @param params   url parameters after question mark
     * @param callback null if no need callback
     */
    protected synchronized static void request(String path, Map<String, String> params, final Callback callback) {

        if (Jiaozi.okhttpClient == null) {
            OkHttpClient.Builder builder = new OkHttpClient.Builder();

            Jiaozi.okhttpClient = builder
                    .connectTimeout(10, TimeUnit.SECONDS)
                    .writeTimeout(10, TimeUnit.SECONDS)
                    .readTimeout(10, TimeUnit.SECONDS)
                    .retryOnConnectionFailure(true)
                    .addInterceptor(new RetryInterceptor())
                    .build();
        }

        HttpUrl.Builder url = HttpUrl.parse(domain).newBuilder()
                .addPathSegments(path);
        if (params != null) {
            for (Map.Entry<String, String> entry : params.entrySet()) {
                url.addQueryParameter(entry.getKey(), entry.getValue());
            }
        }

        url.addQueryParameter("pid", getConfig("profile_id"));
        url.addQueryParameter("_jiaozi_uid", getConfig("uuid"));

        long currentTimestamp = System.currentTimeMillis() / 1000;
        url.addQueryParameter("_ts", currentTimestamp + "");
        if (Jiaozi.timeOffset != null) {
            url.addQueryParameter("timestamp", (currentTimestamp + Jiaozi.timeOffset) + "");
        }
        String user_id = getConfig("user_id");
        if (user_id != null && !user_id.isEmpty()) {
            url.addQueryParameter("user_id", user_id);
        }
        String variation_key = getVariationKey(Jiaozi.current_exp_id);
        String variation = getConfig(variation_key);
        if (variation != null && !variation.isEmpty()) {
            url.addQueryParameter("exp_var", current_exp_id + ":" + variation);
        }


        final Request request = new Request
                .Builder()
                .url(url.build())
                .header("user-agent", getUserAgent())
                .build();

        okhttpClient.newCall(request).enqueue(new okhttp3.Callback() {
            @Override
            public void onFailure(Call call, IOException e) {
                Log.e(K_APPLICATION, "request error:" + request.url().toString(), e);
                String cause = "";
                try {
                    cause = e.getCause().toString();
                } catch (Exception get_e) {/*do nothing*/}
                _errorDebug("onFailure url:" + request.url().toString() + " exception:" + e + " ; cause:" + cause);
                if (callback != null) {
                    callback.onResult(false, null);
                }
            }

            @Override
            public void onResponse(Call call, Response response) {
                Log.i(K_APPLICATION, "request finished:" + request.url().toString());
                if (callback != null) {
                    String response_body = null;
                    try {
                        response_body = response.body().string();
                        response.body().close();
                    } catch (Exception ex) {
                        Log.e(K_APPLICATION, ex.getMessage(), ex);
                        _errorDebug("get response body error url:" + request.url() + " exception:" + ex);
                        callback.onResult(false, null);
                    }
                    if (response.code() >= 400) {
                        _errorDebug("response status code wrong url:" + request.url() + " code:" + response.code());
                        callback.onResult(false, null);
                    } else {
                        callback.onResult(true, response_body);
                    }
                }

            }
        });
    }

    private static void _errorDebug(final String errMessage) {
        Log.e(K_APPLICATION, "_errorDebug log:" + errMessage);
        if (!debugNetwork) {
            return;
        }
        try {
            new Thread(new Runnable() {
                @Override
                public void run() {
                    try {
                        StringBuilder sb = new StringBuilder("https://jiaozi-error.codeedu.net/");
                        sb.append("?error=");
                        sb.append(URLEncoder.encode(errMessage, "utf-8"));
                        URL url = new URL(sb.toString());
                        HttpURLConnection urlConnection = (HttpURLConnection) url.openConnection();
                        InputStream in = new BufferedInputStream(urlConnection.getInputStream());
                        in.close();
                        urlConnection.disconnect();
                    } catch (Exception ex) {
                        Log.e(K_APPLICATION, "_errorDebug exception", ex);
                    }
                }
            }).start();
        } catch (Exception ex) {
            Log.e(K_APPLICATION, "_errorDebug exception", ex);
        }
    }

    private static String userAgent = null;

    private static String getUserAgent() {
        if (userAgent == null) {
            try {
                String packageName = context.getPackageName();
                //replace not allow character eg:chinese
                String model = Build.MODEL.replaceAll("[^a-zA-Z_0-9\\- ,\\+\\(\\)]", "?");
                userAgent = String.format("%s/%s Android/%s (%s)", packageName, getAppVersion(), Build.VERSION.RELEASE, model);
            } catch (Exception ex) {
                Log.e(K_APPLICATION, ex.getMessage(), ex);
                userAgent = "unknown agent";
            }
        }
        return userAgent;
    }

    private static String getAppVersion() {
        try {
            return context.getPackageManager().
                    getPackageInfo(context.getPackageName(), 0).versionName;
        } catch (Exception ex) {
            Log.e(K_APPLICATION, ex.getMessage(), ex);
            return "-1.-1.-1";
        }

    }

    private static String getVariationKey(String experiment_id) {
        return "exp." + experiment_id + ".var";
    }

}

/**
 * Retry request after IOException
 */
class RetryInterceptor implements Interceptor {

    //max request times
    private static final int MAX_RETRY = 10;
    //sleep after failed request
    private static final long REQUEST_GAP = 30_000;

    private static final String TAG = "RetryInterceptor";

    @Override
    public Response intercept(Chain chain) throws IOException {

        IOException exception = null;
        for (int i = 0; i < MAX_RETRY; i++) {
            exception = null;
            try {
                long start = System.currentTimeMillis() / 1000;
                Response response = chain.proceed(chain.request());
                long end = System.currentTimeMillis() / 1000;
                syncServerTime(start,end,response);
                return response;
            } catch (IOException e) {
                exception = e;
                Log.e(TAG, "request failed:" + i, e);
            }

            //sleep after last failed request
            if (i + 1 == MAX_RETRY) continue;
            try {
                Thread.sleep(REQUEST_GAP * (i + 1));
            } catch (InterruptedException e) {
                Log.e(TAG, "request gap sleep failed", e);
            }
        }
        throw exception;
    }

    private void syncServerTime(long start,long end,Response response){
        try{
            if (end - start < 5) {
                /**
                 * base on test.
                 * during request.the most time consume part is connect.
                 * after connect.response time only need hundreds of milliseconds
                 * so the response http header date can equal to current server time
                 */
                if (Jiaozi.timeOffset == null || Math.random() > 0.95) {
                    long serverTimestamp = response.headers().getDate("date").getTime() / 1000;
                    Jiaozi.timeOffset = (int) (serverTimestamp - end);
                    Log.i(TAG, "http_time_offset:" + Jiaozi.timeOffset);
                }
            } else {
                Log.e(TAG, "http_request_duration_too_long:" + (end - start));
            }
            Date d = response.headers().getDate("date");
            Log.d(TAG, "http_timestamp:" + d.getTime() + "\t request_time:" + (end - start) + "\t end_time:" + end);
        }catch (Exception ex){
            Log.e(TAG,"sync server time exception",ex);
        }
    }
}

