## Android SDK Quick Start

example code

```java
//init with current activity
Jiaozi.init(this);

//config with profile id and device uuid
Jiaozi.config(PROFILE_ID,DEVICE_UUID);

//after init & config.we can track user event
Jiaozi.track("user","login","home_screen","by_email");

//if experiment is setup.this method obtain the variation
Jiaozi.getVariation(experiment_id, new jiaozi.Callback() {
    @Override
    public void onResult(boolean success, Object variation) {
        Log.v(Boolean.toString(success),variation.toString());
    }
});
```