import Foundation
import UIKit

/// Modified by Matomo
///
public final class Jiaozi: NSObject {
    private static let SDK_ID = "jiaozi_sdk"

    private var queue: Queue
    private var dispatcher: Dispatcher

    private static var domain: String?

    /// This logger is used to perform logging of all sorts of Matomo related information.
    /// Per default it is a `DefaultLogger` with a `minLevel` of `LogLevel.warning`. You can
    /// set your own Logger with a custom `minLevel` or a complete custom logging mechanism.
    internal var logger: Logger = DefaultLogger(minLevel: .debug)

    // static shared instance
    internal static var _sharedInstance: Jiaozi?

    /// jiaozi standalone instances
    @objc public static let shared = Jiaozi()

    /// start tracker with server domain,this should first be called
    ///
    /// - Parameter domain: jiaozi server domain,get from DevOps
    @objc public static func start(domain: String) {
        Jiaozi.domain = domain
    }

    private override init() {
        let queue = MemoryQueue()
        let dispatcher = URLSessionDispatcher(baseURL: Jiaozi.domain!)
        self.queue = queue
        self.dispatcher = dispatcher
        super.init()
        startDispatchTimer()
    }

    /// Setup profileId and uuid for current app
    ///
    /// - Parameters:
    ///   - profileId: id for this app that generate by jiaozi server
    ///   - uuid: uniq device id,if nil,then will auto generate a new uuid
    @objc public func config(profileId: String, uuid: String? = nil) {
        saveConfig(key: "profileId", value: profileId)
        if uuid != nil {
            saveConfig(key: "uuid", value: uuid!)
        }
    }

    /// Setup User id after user login
    ///
    /// - Parameter userId: app user id
    @objc public func setUserId(userId: String) {
        saveConfig(key: "userId", value: userId)
    }

    /// Remote User id after user logout
    @objc public func removeUserId() {
        removeConfig(key: "userId")
    }

    private static var _config = [String: String]()
    private static var _userDefaults: UserDefaults?

    internal func saveConfig(key: String, value: String) {
        if Jiaozi._userDefaults == nil {
            Jiaozi._userDefaults = UserDefaults(suiteName: Jiaozi.SDK_ID)
        }
        Jiaozi._userDefaults?.set(value, forKey: key)
        Jiaozi._config[key] = value
    }

    internal func getConfig(key: String) -> String? {
        if let val = Jiaozi._config[key] {
            return val
        }
        if Jiaozi._userDefaults == nil {
            Jiaozi._userDefaults = UserDefaults(suiteName: Jiaozi.SDK_ID)
        }
        return Jiaozi._userDefaults?.string(forKey: key)
    }

    internal func removeConfig(key: String) {
        Jiaozi._config.removeValue(forKey: key)
        if Jiaozi._userDefaults == nil {
            Jiaozi._userDefaults = UserDefaults(suiteName: Jiaozi.SDK_ID)
        }
        Jiaozi._userDefaults?.removeObject(forKey: key)
    }

    private static var experimentId: String?

    /// get ab test variation if there is a running experiments
    ///
    /// - Parameters:
    ///   - experimentId: abtest experiment id.one abtest a experiment id.ask for product manager
    ///   - callback: a func callback when a variation is ensure.maybe -1,0,1,... or nil when the experiment is stop
    public func getVariation(experimentId: String, callback: @escaping ((_: Int?) -> Void)) {
        Jiaozi.experimentId = experimentId
        let url = "\(Jiaozi.domain!)/experiments/\(getConfig(key: "profileId")!).json"
        dispatcher.request(url: url, method: "GET", callback: { success, data in
            if success != true {
                return callback(nil)
            }
            let responseJson = try? JSONSerialization.jsonObject(with: data!, options: [])
            var match_experiment: [String: Any]?
            if let responseJson = responseJson as? [NSObject] {
                for experiment in responseJson {
                    let experiment = experiment as? [String: Any]
                    let exp_id = experiment!["experiment_id"] as? String
                    if exp_id != experimentId {
                        continue
                    }
                    let filter = experiment!["filter"] as? [String: String]
                    if filter!["os"] != nil, "iOS".caseInsensitiveCompare(filter!["os"]!) != .orderedSame {
                        break
                    }
                    if filter!["client_version"] != nil, self.dispatcher.getAppVersion().caseInsensitiveCompare(filter!["client_version"]!) != .orderedSame {
                        break
                    }
                    // final match
                    match_experiment = experiment!
                    break
                }

                let variation_key = self.getVariationKey(experimentId: experimentId)
                guard match_experiment != nil else {
                    self.removeConfig(key: variation_key)
                    callback(nil)
                    return
                }
                let savedVariation = self.getConfig(key: variation_key)
                if savedVariation != nil {
                    callback(Int(savedVariation!))
                    return
                }

                let traffic_in_exp = match_experiment!["traffic_in_exp"] as! Double
                var guess = Double.random(in: 0 ..< 1)
                if guess > traffic_in_exp {
                    self.saveConfig(key: variation_key, value: "-1")
                    callback(-1)
                    return
                }

                guess = Double.random(in: 0 ..< 1)
                var weight = 0.0
                let variations = match_experiment!["variations"] as? [[String: Double]]
                for variation in variations! {
                    weight += variation["weight"]!
                    if guess < weight {
                        self.saveConfig(key: variation_key, value: "\(Int(variation["index"]!))")
                        callback(Int(variation["index"]!))
                        return
                    }
                }
            } else {
                callback(nil)
            }
        })
    }

    func getVariationKey(experimentId: String) -> String {
        return "exp.\(experimentId).var"
    }

    // enqueue event
    internal func queue(event: Event) {
        guard Thread.isMainThread else {
            DispatchQueue.main.sync {
                self.queue(event: event)
            }
            return
        }
        logger.verbose("Queued event: \(event)")
        queue.enqueue(event: event)
    }

    private(set) var isDispatching = false

    /// Manually start the dispatching process. You might want to call this method in AppDelegates `applicationDidEnterBackground` to transmit all data
    /// whenever the user leaves the application.
    @objc internal func dispatch() {
        guard !isDispatching else {
            logger.verbose("Event is already dispatching.")
            return
        }
        guard queue.eventCount > 0 else {
            logger.info("No need to dispatch. Dispatch queue is empty.")
            startDispatchTimer()
            return
        }
        logger.info("Start dispatching events")
        isDispatching = true
        doDispatch()
    }

    private func doDispatch() {
        guard Thread.isMainThread else {
            DispatchQueue.main.sync {
                self.doDispatch()
            }
            return
        }
        queue.first { event in
            guard event != nil else {
                self.isDispatching = false
                self.startDispatchTimer()
                self.logger.info("Finished dispatching events")
                return
            }

            self.dispatcher.send(event: event!, callback: { success, _ in
                if success {
                    DispatchQueue.main.async {
                        self.queue.remove(event: event!, completion: {
                            self.logger.info("Dispatched event: \(event!.id).")
                            DispatchQueue.main.async {
                                self.doDispatch()
                            }
                        })
                    }
                } else {
                    self.isDispatching = false
                    self.startDispatchTimer()
                    //                self.logger.warning("Failed dispatching events with error \(error)")
                    self.logger.warning("Failed dispatching events with error")
                }

            })
        }
    }

    // MARK: dispatch timer

    internal var dispatchInterval: TimeInterval = 1.0 {
        didSet {
            startDispatchTimer()
        }
    }

    private var dispatchTimer: Timer?

    private func startDispatchTimer() {
        guard Thread.isMainThread else {
            DispatchQueue.main.sync {
                self.startDispatchTimer()
            }
            return
        }
        guard dispatchInterval > 0 else { return } // Discussion: Do we want the possibility to dispatch synchronous? That than would be dispatchInterval = 0
        if let dispatchTimer = dispatchTimer {
            dispatchTimer.invalidate()
            self.dispatchTimer = nil
        }
        dispatchTimer = Timer.scheduledTimer(timeInterval: dispatchInterval, target: self, selector: #selector(dispatch), userInfo: nil, repeats: false)
    }

    /// track user behavior use event,action,label or other level
    ///
    /// - Parameters:
    ///   - category: first level such as "song","purchase","user"
    ///   - action: second level such as "play","login"
    ///   - label: some item id
    ///   - number: if has a number value
    ///   - extra: extra info
    public func track(eventWithCategory category: String, action: String, label: String? = nil, value: Float? = nil, extra: [String: String]? = nil) {
        var variation: String?
        if Jiaozi.experimentId != nil {
            variation = getConfig(key: getVariationKey(experimentId: Jiaozi.experimentId!))
        }
        let event = Event(profileId: getConfig(key: "profileId")!, uuid: getConfig(key: "uuid")!, eventCategory: category, eventAction: action, eventLabel: label, eventValue: value, userId: getConfig(key: "userId"), eventExtra: extra, experimentId: Jiaozi.experimentId, variation: variation)
        queue(event: event)
    }
}

// Objective-c compatibility extension
@objc public protocol VariationCallback {
    func completionHandler(variation: NSNumber?)
}

// @objc public typealias variationCompletion = (NSNumber)->Void
extension Jiaozi {
    // see track above
    @objc public func track(eventWithCategory category: String, action: String, label: String? = nil, number: NSNumber? = nil, extra: NSDictionary? = nil) {
        let value = number == nil ? nil : number!.floatValue
        track(eventWithCategory: category, action: action, label: label, value: value, extra: extra as? [String: String])
    }

    // see getVariation above
    @objc public func getVariation(experimentId: String, callback: VariationCallback) {
        getVariation(experimentId: experimentId) { variation in
            var nsVariation: NSNumber?
            if variation != nil {
                nsVariation = NSNumber(value: variation!)
            }
            callback.completionHandler(variation: nsVariation)
        }
    }
}

internal protocol Queue {
    var eventCount: Int { get }

    mutating func enqueue(event: Event, completion: (() -> Void)?)

    func first(completion: (_ item: Event?) -> Void)

    /// Removes the events from the queue
    mutating func remove(event: Event, completion: () -> Void)
}

extension Queue {
    // for default nil completion
    mutating func enqueue(event: Event, completion: (() -> Void)? = nil) {
        enqueue(event: event, completion: completion)
    }
}

internal final class MemoryQueue: NSObject, Queue {
    private var items = [Event]()

    public var eventCount: Int {
        return items.count
    }

    public func enqueue(event: Event, completion: (() -> Void)? = nil) {
        //        assertMainThread()
        items.append(event)
        completion?()
    }

    public func first(completion: (_ item: Event?) -> Void) {
        //        assertMainThread()
        //        let amount = [limit,eventCount].min()!
        //        let dequeuedItems = Array(items[0..<amount])
        completion(items.count > 0 ? items[0] : nil)
    }

    public func remove(event: Event, completion: () -> Void) {
        //        assertMainThread()
        //        items = items.filter({ event in !events.contains(where: { eventToRemove in eventToRemove.uuid == event.uuid })})
        items = items.filter { $0.id != event.id }
        completion()
    }
}

internal protocol Dispatcher {
    var baseURL: String { get }

    var userAgent: String? { get }

    func send(event: Event, callback: @escaping (_ success: Bool, _ data: Data?) -> Void)

    func getAppVersion() -> String

    func request(url: String, method: String, callback: @escaping (_ success: Bool, _ data: Data?) -> Void)
}

internal final class URLSessionDispatcher: Dispatcher {
    private let timeout: TimeInterval
    private let session: URLSession
    public let baseURL: String

    public private(set) var userAgent: String?

    /// Generate a URLSessionDispatcher instance
    ///
    /// - Parameters:
    ///   - baseURL: The url of the Matomo server. This url has to end in `piwik.php`.
    public init(baseURL: String) {
        self.baseURL = baseURL
        timeout = 10
        session = URLSession.shared
        userAgent = defaultUserAgent()
    }

    private func defaultUserAgent() -> String {
        let bundleID = Bundle.main.bundleIdentifier
        let appVersion = getAppVersion()
        let systemVersion = UIDevice.current.systemVersion
        let model = UIDevice.current.name
        return String(format: "%@/%@ iOS/%@ (%@)", bundleID ?? "unknown", appVersion, systemVersion, model)
    }

    public func getAppVersion() -> String {
        return Bundle.main.infoDictionary!["CFBundleShortVersionString"] as? String ?? "-1,-1,-1"
    }

    public func send(event: Event, callback: @escaping (_ success: Bool, _ data: Data?) -> Void) {
        let queryParams = event.queryParams()

        //        let request = buildRequest(baseURL: "\(baseURL)/collect_img.gif?\(queryParams)", method: "GET")
        request(url: "\(baseURL)/collect_img.gif?\(queryParams)", method: "GET", callback: callback)
        //        send(request: request, success: success, failure: failure)
    }

    internal func request(url: String, method: String, callback: @escaping (_ success: Bool, _ data: Data?) -> Void) {
        var request = URLRequest(url: URL(string: url)!, cachePolicy: .reloadIgnoringCacheData, timeoutInterval: timeout)
        request.httpMethod = method
        userAgent.map { request.setValue($0, forHTTPHeaderField: "User-Agent") }

        let task = session.dataTask(with: request) { data, _, error in
            guard error == nil || data != nil else {
                return callback(false, nil)
            }
            return callback(true, data)
        }
        task.resume()
    }
}

/// Events
internal struct Event {
    let id = NSUUID()
    let uuid: String?
    let userId: String?
    let profile_id: String

    let eventCategory: String?
    let eventAction: String?
    let eventLabel: String?
    let eventValue: Float?
    let eventExtra: [String: String]?
    let _ts = Int64(Date().timeIntervalSince1970)

    let experimentId: String?
    let variation: String?

    public init(profileId: String, uuid: String, eventCategory: String? = nil, eventAction: String? = nil, eventLabel: String? = nil,
                eventValue: Float? = nil, userId: String? = nil, eventExtra: [String: String]? = nil, experimentId: String? = nil, variation: String? = nil) {
        profile_id = profileId
        self.uuid = uuid
        self.eventCategory = eventCategory
        self.eventAction = eventAction
        self.eventLabel = eventLabel
        self.eventValue = eventValue
        self.userId = userId
        self.eventExtra = eventExtra
        self.experimentId = experimentId
        self.variation = variation
    }

    internal func queryParams() -> String {
        let queryParams = queryItems.compactMap({ item in
            guard let value = item.value,
                let encodeValue = value.addingPercentEncoding(withAllowedCharacters: .urlHostAllowed) else {
                return nil
            }
            return "\(item.name)=\(encodeValue)"
        }).joined(separator: "&")
        return queryParams
    }

    var eventExtraJson: String? {
        do {
            guard eventExtra != nil else {
                return nil
            }
            let jsonData = try JSONSerialization.data(withJSONObject: eventExtra!)
            return String(data: jsonData, encoding: String.Encoding.ascii)!
        } catch {
            return nil
        }
    }

    /// experiment compact info
    var expVar: String? {
        guard experimentId != nil, variation != nil else {
            return nil
        }
        return "\(experimentId ?? ""):\(variation ?? "")"
    }

    // 想用反射处理，但是...
    var queryItems: [URLQueryItem] {
        let items = [
            URLQueryItem(name: "type", value: "event"),
            URLQueryItem(name: "_jiaozi_uid", value: uuid),
            URLQueryItem(name: "category", value: eventCategory),
            URLQueryItem(name: "action", value: eventAction),
            URLQueryItem(name: "label", value: eventLabel),
            URLQueryItem(name: "value_number", value: eventValue != nil ? "\(eventValue!)" : nil),
            URLQueryItem(name: "value", value: eventExtraJson),
            URLQueryItem(name: "user_id", value: userId),
            URLQueryItem(name: "pid", value: profile_id),
            URLQueryItem(name: "_ts", value: String(_ts)),
            URLQueryItem(name: "exp_var", value: expVar),
        ].filter { $0.value != nil }

        return items
    }
}

/// support libs
@objc internal enum LogLevel: Int {
    case verbose = 10
    case debug = 20
    case info = 30
    case warning = 40
    case error = 50

    var shortcut: String {
        switch self {
        case .error: return "E"
        case .warning: return "W"
        case .info: return "I"
        case .debug: return "D"
        case .verbose: return "V"
        }
    }
}

/// The Logger protocol defines a common interface that is used to log every message from the sdk.
/// You can easily writer your own to perform custom logging.
@objc internal protocol Logger {
    /// This method should perform the logging. It can be called from every thread. The implementation has
    /// to handle synchronizing different threads.
    ///
    /// - Parameters:
    ///   - message: A closure that produces the message itself.
    ///   - level: The loglevel of the message.
    ///   - file: The filename where the log was created.
    ///   - function: The funciton where the log was created.
    ///   - line: Then line where the log was created.
    func log(_ message: @autoclosure () -> String, with level: LogLevel, file: String, function: String, line: Int)
}

extension Logger {
    func verbose(_ message: @autoclosure () -> String, file: String = #file, function: String = #function, line: Int = #line) {
        log(message, with: .verbose, file: file, function: function, line: line)
    }

    func debug(_ message: @autoclosure () -> String, file: String = #file, function: String = #function, line: Int = #line) {
        log(message, with: .debug, file: file, function: function, line: line)
    }

    func info(_ message: @autoclosure () -> String, file: String = #file, function: String = #function, line: Int = #line) {
        log(message, with: .info, file: file, function: function, line: line)
    }

    func warning(_ message: @autoclosure () -> String, file: String = #file, function: String = #function, line: Int = #line) {
        log(message, with: .warning, file: file, function: function, line: line)
    }

    func error(_ message: @autoclosure () -> String, file: String = #file, function: String = #function, line: Int = #line) {
        log(message, with: .error, file: file, function: function, line: line)
    }
}

/// This Logger loggs every message to the console with a `print` statement.
@objc internal final class DefaultLogger: NSObject, Logger {
    private let dispatchQueue = DispatchQueue(label: "DefaultLogger", qos: .background)
    private let minLevel: LogLevel

    @objc public init(minLevel: LogLevel) {
        self.minLevel = minLevel
        super.init()
    }

    public func log(_ message: @autoclosure () -> String, with level: LogLevel, file _: String = #file, function _: String = #function, line _: Int = #line) {
        guard level.rawValue >= minLevel.rawValue else { return }
        let messageToPrint = message()
        dispatchQueue.async {
            print("MatomoTracker [\(level.shortcut)] \(messageToPrint)")
        }
    }
}
