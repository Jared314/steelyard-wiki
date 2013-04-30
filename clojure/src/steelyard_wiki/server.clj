(ns steelyard-wiki.server
  (:require [ring.adapter.jetty :refer :all]
            [compojure.core :refer :all]
            [compojure.route :as route]
            [compojure.handler :as handler]
            [clojure.string :as str]
            [clojure.java.jdbc :as j]
            [clojure.java.jdbc.sql :as s]
            [clojure.pprint :as pp])
  (:import [java.io ByteArrayInputStream]
           [com.mchange.v2.c3p0 ComboPooledDataSource]))

(def default-connection-string "Data Source=db.sqlite;Version=3;")

(def dbconn
     {:classname "org.sqlite.JDBC"
      :subprotocol "sqlite"
      :subname "db.sqlite"
      :version "3"})

(defn convert [{value :value type :type binary :is_binary}]
      (let [body (if (= 1 binary) (ByteArrayInputStream. value) value)]
           {:status 200
            :headers {"Content-Type" type}
            :body body}))

(defn get-data [name]
      (let [result (j/query dbconn (s/select [:value :type :is_binary] :page (s/where {:name name :inactive 0}) (s/order-by {:id :desc})))]
           (convert (first result))))

(defn parse-uri [uri]
      (str/replace uri #"^\/" ""))

(defroutes site-routes
  (GET "/*" {uri :uri} (get-data (parse-uri uri)))
  (route/resources "/")
  (route/not-found (str "not found")))

(def app (-> site-routes
             handler/site))

(defn -main [& options]
  (let [port (Integer. (or (first options) 5000))
        mode (keyword (or (second options) :dev))
        connection-string (nth options 2 default-connection-string)]
       (run-jetty app {:port port})))
