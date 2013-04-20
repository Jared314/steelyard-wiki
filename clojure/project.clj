(defproject steelyard-wiki "0.1.0-SNAPSHOT"
  :description "FIXME: write description"
  :url "http://example.com/FIXME"
  :license {:name "Eclipse Public License"
            :url "http://www.eclipse.org/legal/epl-v10.html"}
  :dependencies [[org.clojure/clojure "1.5.1"]
                 [compojure "1.1.5"]
                 [ring/ring-jetty-adapter "1.1.7"]
                 [enlive "1.1.1"]
                 [org.clojure/java.jdbc "0.3.0-alpha1"]
                 [c3p0/c3p0 "0.9.1.2"]
                 [org.xerial/sqlite-jdbc "3.7.2"]
                 [postgresql/postgresql "8.4-702.jdbc4"]]
  :main steelyard-wiki.server
  :min-lein-version "2.0.0")
