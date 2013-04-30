(ns steelyard-wiki.server
  (:require [ring.adapter.jetty :refer :all]
            [compojure.core :refer :all]
            [compojure.route :as route]
            [compojure.handler :as handler]
            [clojure.string :as str]
            [clojure.java.jdbc :as j]
            [clojure.java.jdbc.sql :as s]))

(def default-connection-string "Data Source=db.sqlite;Version=3;")

(def dbconn
      {:classname "org.sqlite.JDBC"
       :subprotocol "sqlite"
       :subname "db.sqlite"
       :version "3"})

(defn get-data [name]
      (last (j/query dbconn (s/select * :page (s/where {:name name}))
                     :row-fn :value)))

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
