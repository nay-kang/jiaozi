package main

import (
	"log"
	"net/http"
	"github.com/streadway/amqp"
	"encoding/json"
)

func failOnError(err error, msg string) {
    if err != nil {
        log.Fatalf("%s: %s", msg, err)
    }
}

type Log struct {
        Cookie []*http.Cookie
        Header http.Header
        Uri    string
}

func jiaozi(rw http.ResponseWriter, req *http.Request) {
        var msg Log

        msg.Cookie = req.Cookies()
        msg.Header = req.Header
        msg.Uri = req.RequestURI

	
	//body := "hello"
	body, _ := json.Marshal(msg)
	send(body)
	log.Printf(" [x] Sent %s", body)
}

var ch amqp.Channel = nil
var q amqp.Queue = nil
func send(msg []byte){
	if ch == nil {
		var err error
		conn, err := amqp.Dial("amqp://guest:guest@localhost:5672/")
		failOnError(err, "Failed to connect to RabbitMQ")
		defer conn.Close()

		ch, err = conn.Channel()
		failOnError(err, "Failed to open a channel")
		defer ch.Close()

		q, err = ch.QueueDeclare(
			"jiaozi_1", // name
			false,   // durable
			false,   // delete when unused
			false,   // exclusive
			false,   // no-wait
			nil,     // arguments
			)
		failOnError(err, "Failed to declare a queue")
	}
	err := ch.Publish(
	"",     // exchange
	q.Name, // routing key
	false,  // mandatory
	false,  // immediate
	amqp.Publishing{
		ContentType: "text/plain",
		Body:        []byte(msg),
	})
	failOnError(err, "Failed to publish a message")

}
func main() {
	
        http.HandleFunc("/", jiaozi)
        http.ListenAndServe(":8080", nil)
}
