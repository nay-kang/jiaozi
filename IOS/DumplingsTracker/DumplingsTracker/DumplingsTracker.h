//
//  DumplingsTracker.h
//  DumplingsTracker
//
//  Created by lee on 2017/3/16.
//  Copyright © 2017年 chicv. All rights reserved.
//

#import <Foundation/Foundation.h>

@interface DumplingsTracker : NSObject

+(instancetype) sharedTracker;

+(void) eventWithName:(NSString *) eventName parameters:(NSDictionary *) parameters;

@property(nonatomic, strong) NSString *pid;
@property(nonatomic, strong) NSString *idfa;

@end
