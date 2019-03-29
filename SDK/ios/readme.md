## iOS SDK Quick Start

Swift Example Code
```swift
// init with domain
Jiaozi.start(domain: "https://example.com")
// config with profile id and device uuid
Jiaozi.shared.config(profileId: "PROFILE_ID",uuid: "DEVICE_UUID")
// after init & config.we can track user event
Jiaozi.shared.track(eventWithCategory: "user", action: "login", label: "home_screen", value: 10.5,extra:["extra_key":"extra_val"])

// if experiment is setup.this method obtain the variation
Jiaozi.shared.getVariation(experimentId: "EXPERIMENT_ID") { (variation) in
    NSLog("variation is:\(variation)")
}

// user login
Jiaozi.shared.setUserId(userId:"app_user_id")
// user logout
Jiaozi.shared.removeUserId()
```

Objective-c Example Code
```objc
// init with domain
[Jiaozi startWithDomain:@"https://example.com"];
// config with profile id and device uuid
[Jiaozi.shared configWithProfileId:@"PROFILE_ID" uuid:@"DEVICE_UUID"];
// after init & config.we can track user event
[Jiaozi.shared trackWithEventWithCategory:@"user" action:@"login" label:@"home_screen" number:[NSNumber numberWithFloat:10.5] extra:@{@"extra_key":@"extra_value"}];

// if experiment is setup.this method obtain the variation

@interface VariationCallback : NSObject<VariationCallback>

@end

@implementation VariationCallback


- (void)completionHandlerWithVariation:(NSNumber * _Nullable)variation {
    NSLog(@"%@",variation);
}

// and call get variation
[Jiaozi.shared getVariationWithExperimentId:@"EXPERIMENT_ID" callback:[VariationCallback new]];

// user login
[Jiaozi.shared setUserIdWithUserId:@"app_user_id"];
// user logout
[Jiaozi.shared removeUserId];
```