(ns steelyard-wiki.core)

(defprotocol command
  (execute! [db]))

(defrecord resource [:name :value :type :version])

(defrecord save-resource [:resource]
  command
  (execute! [db] nil))

(defrecord delete-resource [:name]
  command
  (execute! [db] nil))

(defn execute-command [command]
      (let [db nil]
           (execute! command db)))


