# encoding: utf-8

require 'csv'

# Create large CSV file
CSV.open("csv_file.csv", "wb") do |csv|
	0.upto(900_000) do |i|
		csv << [sprintf("%06d",i), 
			      i.modulo(100).to_s + "0µl",
			      "¥" + i.modulo(1000).to_s]
	end
end