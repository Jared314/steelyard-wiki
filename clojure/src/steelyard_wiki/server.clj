(ns steelyard-wiki.server
  (:require [ring.adapter.jetty :refer :all]
            [compojure.core :refer :all]
            [compojure.route :as route]
            [compojure.handler :as handler]
            [clojure.string :as str]))

(defn parse-uri [uri]
      (str/replace uri #"^\/" ""))

(defroutes site-routes
  (GET "/*" {uri :uri} (str "<h1>" (parse-uri uri) "</h1>"))
  (route/resources "/")
  (route/not-found (str "not found")))

(def app (-> site-routes
             handler/site))

(defn -main [& options]
  (let [port (Integer. (or (first options) 5000))
        mode (keyword (or (second options) :dev))]
       (run-jetty app {:port port})))
