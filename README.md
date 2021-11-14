# DMARC Policy Analyzer
Quick and Dirty PHP script to recursively analyze and summarize DMARC XML reports.

The DNS DMARC policy record affords a way to collect "daily" reports where email/domain activity has been encountered. The receiving address is set via the `rua` argument in DNS.

I wrote a [quick primer on implementing DMARC for alias domains](https://mzonline.com/blog/2020-11/implementing-dmarc-alias-domains) (those which aren't used for sending email and should therefore have a 100% "failure" or rejection rate). The blog post contains the basic/requisite details to get set up with DMARC policy in DNS.

## Requirements
* PHP (run on 7.x, though the script should behave in 5.x if that's your jam)
* n > 0 DMARC reports (these all come in a standard XML format)
  
## How to Use
1. Set up DMARC policy to receive policy reports
2. Collect and extract reports for a period of time (most arrive as .zip files)
3. Configure lines 5-6 of `analyzeReports.php` to your accumulated data (and wherever you'd like a flat CSV of failure results to be written)
4. Invoke the script (e.g. `php analyzeReports.php`) and wait for completion (usually a few seconds)
5. Celebrate the results (or not)
6. Further process details based on the data written to the `$rawFailureOutputDataset` file (Excel, Google Sheets, PowerBI, etc.), if desired

## Example Files Included
Included is a real DMARC report received (modified for example purposes) that can be used out of the box with this script: `google.com!example.com!1635724800!1635811199.xml`

Also included is an example `rawfailuredata.csv` file for further analysis (step 6 above). This was generated from the example file in the repo.