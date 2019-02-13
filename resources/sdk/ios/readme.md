## iOS SDK Quick Start

example code

```objc
// init with domain
[Jiaozi startWithDomain:@"https://example.com"];
// config with profile id and device uuid
[Jiaozi.shared configWithProfileId:@"PROFILE_ID" uuid:@"DEVICE_UUID"];
// after init & config.we can track user event
[Jiaozi.shared trackWithEventWithCategory:@"user" action:@"login" label:@"home_screen" number:[NSNumber numberWithFloat:10.5] extra:@{@"extra_key":@"extra_value"}];

// if experiment is setup.this method obtain the variation

@interface VariationCallback : NSObject<Callback>

@end

@implementation VariationCallback


- (void)completionHandlerWithVariation:(NSNumber * _Nullable)variation {
    NSLog(@"%@",variation);
}

// and call get variation
[Jiaozi.shared getVariationWithExperiment_id:@"EXPERIMENT_ID" callback:[VariationCallback new]];
```