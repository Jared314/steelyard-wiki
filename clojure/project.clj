(defproject steelyard-wiki "0.1.0-SNAPSHOT"
  :description "FIXME: write description"
  :url "http://example.com/FIXME"
  :license {:name "Eclipse Public License"
            :url "http://www.eclipse.org/legal/epl-v10.html"}
  :dependencies [[org.clojure/clojure "1.5.1"]
                 [compojure "1.1.5"]
                 [ring "1.1.8"]
                 [enlive "1.1.1"]
                 [korma "0.3.0-RC5"]]
  :main steelyard-wiki.server
  :min-lein-version "2.0.0")
